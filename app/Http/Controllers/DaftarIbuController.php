<?php

namespace App\Http\Controllers;

use App\Models\House;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Daftar Ibu per Rumah — rekap nama ibu/istri tiap rumah untuk keperluan PKK.
 *
 * Nama yang ditampilkan diprioritaskan dari anggota berlabel "Istri".
 * Kalau tidak ada, jatuh ke Kepala Keluarga (atau anggota perempuan dewasa lain)
 * dan barisnya ditandai supaya ketahuan mana yang datanya belum lengkap.
 */
class DaftarIbuController extends Controller
{
    private function authorizePkk(): void
    {
        if (!in_array(auth()->user()?->role, ['ketua', 'pkk'])) {
            abort(403, 'Halaman ini hanya untuk Ketua RT & Ibu PKK.');
        }
    }

    /** Query rumah + seluruh anggota keluarga (pemilik & penyewa), urut natural. */
    private function houses()
    {
        return House::with([
                'owner.residentProfile.idCards',
                'tenant.residentProfile.idCards',
            ])
            ->orderByRaw("REGEXP_SUBSTR(blok, '^[A-Za-z]+'), CAST(REGEXP_SUBSTR(blok, '[0-9]+') AS UNSIGNED), CAST(nomor AS UNSIGNED)")
            ->get();
    }

    /** Label bebas diketik warga — normalkan supaya "istri", "ISTRI", "Istri 1" tetap kebaca. */
    private function isIstri(?string $label): bool
    {
        return $label && str_contains(mb_strtolower($label), 'istri');
    }

    private function isKepalaKeluarga(?string $label): bool
    {
        if (!$label) return false;
        $l = mb_strtolower($label);
        return str_contains($l, 'kepala keluarga') || str_contains($l, 'kk');
    }

    private function isPerempuan(?string $jk): bool
    {
        $v = mb_strtolower(trim((string) $jk));
        return $v === 'p' || str_contains($v, 'perempuan') || $v === 'wanita';
    }

    /**
     * Susun satu baris rekap untuk sebuah rumah.
     *
     * status:
     *   ada_istri      — ketemu anggota berlabel "Istri" dan punya nama
     *   kk_perempuan   — kepala keluarganya sendiri perempuan (janda / ibu tunggal)
     *   perempuan_lain — tidak ada label istri, tapi ada anggota perempuan dewasa
     *   kk_saja        — hanya ada kepala keluarga / anggota lain (semua laki-laki)
     *   belum_didata   — profil keluarga belum diisi sama sekali
     */
    private function buildRow(House $house): array
    {
        $anggota = [];

        foreach (['owner' => 'Pemilik', 'tenant' => 'Kontrak'] as $rel => $slot) {
            $person = $house->{$rel};
            if (!$person || !$person->residentProfile) continue;

            foreach ($person->residentProfile->idCards as $card) {
                $nama = trim((string) $card->nama);
                if ($nama === '') continue; // baris tanpa nama (mis. scan KK saja) tidak dihitung

                $anggota[] = [
                    'id'            => $card->id,
                    'nama'          => $nama,
                    'label'         => $card->label ?: '-',
                    'jenis_kelamin' => $this->isPerempuan($card->jenis_kelamin) ? 'P'
                        : (mb_strtolower(trim((string) $card->jenis_kelamin)) !== '' ? 'L' : null),
                    'pekerjaan'     => $card->pekerjaan ?: null,
                    'is_istri'      => $this->isIstri($card->label),
                    'slot'          => $slot,
                ];
            }
        }

        $kepalaKeluarga = null;
        foreach ($anggota as $a) {
            if ($this->isKepalaKeluarga($a['label'])) { $kepalaKeluarga = $a['nama']; break; }
        }

        // Prioritas 1: anggota berlabel "Istri".
        $pilihan = null; $status = null;
        foreach ($anggota as $a) {
            if ($a['is_istri']) { $pilihan = $a; $status = 'ada_istri'; break; }
        }

        // Prioritas 2: perempuan lain yang bukan anak — kepala keluarga perempuan
        // (mis. janda / ibu tunggal) dipisah statusnya karena namanya memang sudah benar.
        if (!$pilihan) {
            foreach ($anggota as $a) {
                if ($a['jenis_kelamin'] === 'P' && !str_contains(mb_strtolower($a['label']), 'anak')) {
                    $pilihan = $a;
                    $status = $this->isKepalaKeluarga($a['label']) ? 'kk_perempuan' : 'perempuan_lain';
                    break;
                }
            }
        }

        // Prioritas 3: kepala keluarga / anggota pertama yang ada.
        if (!$pilihan && $anggota) {
            $pilihan = null;
            foreach ($anggota as $a) {
                if ($this->isKepalaKeluarga($a['label'])) { $pilihan = $a; break; }
            }
            $pilihan = $pilihan ?: $anggota[0];
            $status = 'kk_saja';
        }

        // Prioritas 4: belum ada anggota terdata — pakai nama akun pemilik/penyewa.
        if (!$pilihan) {
            $penghuni = $house->tenant ?: $house->owner;
            $status = 'belum_didata';
            $pilihan = [
                'nama'  => $penghuni?->name ?: '-',
                'label' => 'Akun warga',
                'slot'  => $house->tenant ? 'Kontrak' : 'Pemilik',
            ];
        }

        $keterangan = match ($status) {
            'ada_istri'      => null,
            'kk_perempuan'   => 'Kepala keluarga perempuan (tidak ada data istri terpisah)',
            'perempuan_lain' => 'Label "Istri" belum ada — nama diambil dari anggota perempuan lain (' . $pilihan['label'] . ')',
            'kk_saja'        => 'Data istri belum ada — nama diambil dari ' . ($kepalaKeluarga ? 'Kepala Keluarga' : 'anggota keluarga (' . $pilihan['label'] . ')'),
            'belum_didata'   => 'Data keluarga belum didata — nama diambil dari akun warga',
        };

        $penghuni = $house->tenant ?: $house->owner;

        return [
            'id'              => $house->id,
            'rumah'           => $house->blok . '/' . $house->nomor,
            'nama'            => $pilihan['nama'],
            'sumber_label'    => $pilihan['label'],
            'status'          => $status,
            'keterangan'      => $keterangan,
            'kepala_keluarga' => $kepalaKeluarga,
            // Kontak diambil dari akun warga (pemilik/penyewa) — belum tentu nomor
            // si ibu. Nama pemegang akun ikut dikirim supaya tidak salah tafsir.
            'kontak'          => $penghuni?->phone_number,
            'kontak_nama'     => $penghuni?->name,
            'kontak_slot'     => $house->tenant ? 'Penyewa' : 'Pemilik',
            'slot'            => $pilihan['slot'] ?? null,
            'status_huni'     => $house->status_huni,
            'jumlah_anggota'  => count($anggota),
            'anggota'         => $anggota,
        ];
    }

    /**
     * Saring rumah sesuai lingkup yang diminta.
     * scope: berpenghuni (default) | kosong | semua
     */
    private function rowsForScope(string $scope)
    {
        return $this->houses()
            ->filter(function (House $h) use ($scope) {
                if ($scope === 'kosong') return $h->status_huni !== 'berpenghuni';
                if ($scope === 'semua') return true;
                return $h->status_huni === 'berpenghuni';
            })
            ->map(fn (House $h) => $this->buildRow($h))
            ->values();
    }

    private function scopeFrom(Request $request): string
    {
        $scope = (string) $request->query('scope', 'berpenghuni');

        return in_array($scope, ['berpenghuni', 'kosong', 'semua'], true) ? $scope : 'berpenghuni';
    }

    public function index()
    {
        $this->authorizePkk();

        // Kirim semua rumah sekaligus; pergantian lingkup ditangani client-side
        // supaya tidak perlu request ulang (pola sama dengan Calendar & Tagihan).
        $rows = $this->rowsForScope('semua');

        return Inertia::render('Ketua/DaftarIbu', [
            'rows' => $rows,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $this->authorizePkk();

        $scope = $this->scopeFrom($request);
        $rows = $this->rowsForScope($scope);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Ibu');

        // Kolom "Status Huni" hanya relevan kalau rumah kosong ikut diekspor.
        $withStatusHuni = $scope !== 'berpenghuni';
        $lastCol = $withStatusHuni ? 'I' : 'H';

        $judulScope = match ($scope) {
            'kosong' => ' (RUMAH KOSONG)',
            'semua'  => ' (SEMUA RUMAH)',
            default  => '',
        };

        $sheet->setCellValue('A1', 'DAFTAR IBU PER RUMAH — RT-44 SEPINGGAN BARU' . $judulScope);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '9D174D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->setCellValue('A2', 'Dicetak: ' . now()->isoFormat('dddd, D MMMM Y')
            . '  —  Kontak Keluarga adalah nomor akun warga (pemilik/penyewa), belum tentu nomor si ibu.');
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $headers = ['No', 'Rumah'];
        if ($withStatusHuni) $headers[] = 'Status Huni';
        array_push($headers, 'Nama Ibu', 'Sumber Data', 'Nama Kepala Keluarga', 'Kontak Keluarga', 'Atas Nama (Pemilik Nomor)', 'Keterangan');

        $headerRow = 4;
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . $headerRow, $h);
        }
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '9D174D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(22);

        $sumberLabel = [
            'ada_istri'      => 'Istri',
            'kk_perempuan'   => 'Kepala Keluarga (P)',
            'perempuan_lain' => 'Anggota perempuan',
            'kk_saja'        => 'Kepala Keluarga',
            'belum_didata'   => 'Akun warga',
        ];

        // Offset kolom setelah "Rumah" — bergeser 1 kalau kolom Status Huni ikut.
        $c = fn (int $i) => chr(65 + $i + ($withStatusHuni ? 1 : 0));

        $row = 5;
        foreach ($rows as $i => $r) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $r['rumah']);
            if ($withStatusHuni) {
                $sheet->setCellValue('C' . $row, $r['status_huni'] === 'berpenghuni' ? 'Berpenghuni' : 'Kosong');
            }
            $sheet->setCellValue($c(2) . $row, $r['nama']);
            $sheet->setCellValue($c(3) . $row, $sumberLabel[$r['status']]);
            $sheet->setCellValue($c(4) . $row, $r['kepala_keluarga'] ?: '-');
            $sheet->setCellValueExplicit($c(5) . $row, $r['kontak'] ?: '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue($c(6) . $row, $r['kontak'] && $r['kontak_nama']
                ? $r['kontak_nama'] . ' (' . $r['kontak_slot'] . ')'
                : '-');
            $sheet->setCellValue($c(7) . $row, $r['keterangan'] ?: '-');

            // Baris yang datanya perlu dilengkapi diberi latar supaya gampang ditindaklanjuti.
            if (!in_array($r['status'], ['ada_istri', 'kk_perempuan'])) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');
            }

            $row++;
        }

        $lastRow = max($row - 1, 5);
        $sheet->getStyle("A5:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A5:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$c(3)}5:{$c(3)}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $widths = ['A' => 6, 'B' => 12];
        if ($withStatusHuni) {
            $widths['C'] = 14;
            $sheet->getStyle("C5:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        foreach ([2 => 32, 3 => 20, 4 => 32, 5 => 18, 6 => 30, 7 => 55] as $i => $w) {
            $widths[$c($i)] = $w;
        }
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        $sheet->freezePane('A5');

        // Sheet kedua: seluruh anggota keluarga per rumah.
        $detail = $spreadsheet->createSheet();
        $detail->setTitle('Anggota Keluarga');
        $detailHeaders = ['No', 'Rumah', 'Nama', 'Hubungan/Label', 'L/P', 'Pekerjaan'];
        foreach ($detailHeaders as $i => $h) {
            $detail->setCellValue(chr(65 + $i) . '1', $h);
        }
        $detail->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $dRow = 2; $no = 1;
        foreach ($rows as $r) {
            foreach ($r['anggota'] as $a) {
                $detail->setCellValue('A' . $dRow, $no++);
                $detail->setCellValue('B' . $dRow, $r['rumah']);
                $detail->setCellValue('C' . $dRow, $a['nama']);
                $detail->setCellValue('D' . $dRow, $a['label']);
                $detail->setCellValue('E' . $dRow, $a['jenis_kelamin'] ?: '-');
                $detail->setCellValue('F' . $dRow, $a['pekerjaan'] ?: '-');
                $dRow++;
            }
        }
        foreach (['A' => 6, 'B' => 12, 'C' => 32, 'D' => 22, 'E' => 6, 'F' => 24] as $col => $w) {
            $detail->getColumnDimension($col)->setWidth($w);
        }
        $detail->freezePane('A2');

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'daftar-ibu-rt44-' . $scope . '-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
