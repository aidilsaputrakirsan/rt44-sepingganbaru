<?php

namespace App\Services;

use App\Models\House;

/**
 * Sumber tunggal daftar blok yang ada di RT.
 *
 * Blok tidak pernah di-hardcode: daftarnya selalu diturunkan dari huruf awal
 * kolom `houses.blok`. Begitu ada rumah blok baru (mis. "I1/3"), blok itu
 * otomatis muncul sebagai filter laporan dan sebagai pilihan penanda
 * pengeluaran, tanpa perlu ubah kode.
 */
class BlokService
{
    /** Penanda pengeluaran yang melayani semua blok sekaligus. */
    public const SHARED = 'shared';

    /** Nilai filter laporan yang berarti "seluruh RT". */
    public const ALL = 'all';

    private ?array $counts = null;

    /**
     * Jumlah rumah per huruf blok, mis. ['G' => 117, 'H' => 34].
     */
    public function counts(): array
    {
        if ($this->counts === null) {
            $this->counts = House::selectRaw('UPPER(LEFT(blok, 1)) as g, COUNT(*) as c')
                ->whereNotNull('blok')
                ->where('blok', '!=', '')
                ->groupBy('g')
                ->orderBy('g')
                ->pluck('c', 'g')
                ->map(fn ($c) => (int) $c)
                ->toArray();
        }

        return $this->counts;
    }

    /** Daftar huruf blok yang ada, mis. ['G', 'H']. */
    public function letters(): array
    {
        return array_keys($this->counts());
    }

    public function totalHouses(): int
    {
        return array_sum($this->counts());
    }

    /** Huruf blok sebuah rumah. */
    public function letterOf(?House $house): string
    {
        return $house ? strtoupper(substr($house->blok, 0, 1)) : '?';
    }

    /**
     * Nilai yang sah untuk kolom `expenses.blok_group`:
     * semua huruf blok yang ada, ditambah 'shared'.
     */
    public function expenseGroups(): array
    {
        return array_merge($this->letters(), [self::SHARED]);
    }

    /** Aturan validasi `in:...` untuk penanda blok pengeluaran. */
    public function expenseGroupRule(): string
    {
        return 'in:' . implode(',', $this->expenseGroups());
    }

    /**
     * Pilihan penanda untuk form pengeluaran, siap dipakai di dropdown.
     */
    public function expenseOptions(): array
    {
        $options = [[
            'value' => self::SHARED,
            'label' => 'Bersama (semua blok)',
            'hint'  => 'Dibagi otomatis sesuai jumlah rumah tiap blok',
        ]];

        foreach ($this->counts() as $letter => $count) {
            $options[] = [
                'value' => $letter,
                'label' => 'Khusus Blok ' . $letter,
                'hint'  => $count . ' rumah',
            ];
        }

        return $options;
    }

    /**
     * Normalisasi filter blok laporan. Selain blok yang benar-benar ada,
     * apapun isinya dianggap "seluruh RT".
     */
    public function normalizeFilter(?string $blok): string
    {
        $blok = strtoupper(trim((string) $blok));

        return array_key_exists($blok, $this->counts()) ? $blok : self::ALL;
    }

    /**
     * Pecah satu nominal pengeluaran bersama ke tiap blok secara proporsional
     * terhadap jumlah rumah. Sisa pembulatan jatuh ke blok terbesar supaya
     * jumlah seluruh porsi selalu sama persis dengan nominal aslinya.
     *
     * @return array<string, float> mis. ['H' => 1801325.0, 'G' => 6198675.0]
     */
    public function splitShared(float $amount): array
    {
        $counts = $this->counts();
        $total = array_sum($counts);

        if ($total === 0) {
            return [];
        }

        // Blok terbesar ditaruh paling akhir agar menyerap selisih pembulatan.
        $keys = array_keys($counts);
        usort($keys, fn ($a, $b) => $counts[$a] <=> $counts[$b]);

        $shares = [];
        $assigned = 0.0;
        $last = count($keys) - 1;

        foreach ($keys as $i => $key) {
            if ($i === $last) {
                $shares[$key] = round($amount - $assigned, 2);
            } else {
                $share = round($amount * $counts[$key] / $total);
                $shares[$key] = $share;
                $assigned += $share;
            }
        }

        return $shares;
    }

    /** Persentase porsi sebuah blok terhadap seluruh RT. */
    public function sharePercent(string $blok): float
    {
        $total = $this->totalHouses();

        return $total > 0 ? round(($this->counts()[$blok] ?? 0) / $total * 100, 1) : 0;
    }
}
