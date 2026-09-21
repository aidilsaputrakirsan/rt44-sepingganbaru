@php
    $formatCurrency = function ($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    };
    $expenses = $report['expenses'];
    $isBlokView = $report['is_blok_view'];
    $hasShared = collect($expenses)->contains(fn ($e) => $e['is_shared']);
@endphp

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan RT-44 - {{ $report['blok_label'] }} - {{ $report['period_label'] }}</title>
    <style>
        @page {
            margin: 32mm 18mm 20mm 18mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #000000;
            line-height: 1.55;
            font-size: 11.5pt;
            margin: 0;
            padding: 0;
        }

        /* ===== KOP SURAT (berulang di setiap halaman) ===== */
        .kop {
            position: fixed;
            top: -22mm;
            left: 0;
            right: 0;
            height: 20mm;
        }

        .kop table {
            width: 100%;
            border-collapse: collapse;
        }

        .kop td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .kop-logo {
            width: 58px;
        }

        .kop-logo img {
            width: 52px;
            height: 52px;
        }

        .kop-text h1 {
            margin: 0;
            font-family: 'Times', 'Times New Roman', serif;
            font-size: 16pt;
            letter-spacing: 0.5px;
        }

        .kop-text h2 {
            margin: 1px 0 0;
            font-family: 'Times', 'Times New Roman', serif;
            font-size: 10.5pt;
            font-weight: normal;
        }

        .kop-rule {
            border-bottom: 2.5pt solid #000;
            margin-top: 5px;
        }

        .kop-rule-thin {
            border-bottom: 0.75pt solid #000;
            margin-top: 1.5px;
        }

        /* ===== FOOTER (nomor halaman) ===== */
        .footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            border-top: 0.75pt solid #999;
            padding-top: 4px;
            font-size: 8.5pt;
            color: #444;
        }

        .footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer td {
            border: none;
            padding: 0;
        }

        .footer .page-num:after {
            content: counter(page);
        }

        /* ===== JUDUL DOKUMEN ===== */
        .doc-title {
            text-align: center;
            margin-bottom: 18px;
        }

        .doc-title .t1 {
            font-family: 'Times', 'Times New Roman', serif;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-title .t2 {
            font-family: 'Times', 'Times New Roman', serif;
            font-size: 12pt;
            margin-top: 2px;
        }

        .doc-title .t3 {
            font-size: 9.5pt;
            color: #444;
            margin-top: 3px;
        }

        /* ===== JUDUL BAGIAN ===== */
        .section-title {
            font-size: 11.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1.2pt solid #000;
            padding-bottom: 4px;
            margin: 24px 0 9px;
        }

        .section-note {
            font-size: 9.5pt;
            color: #444;
            margin: -4px 0 8px;
        }

        /* ===== TABEL ===== */
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        table.data th {
            background-color: #ededed;
            text-align: left;
            padding: 7px 9px;
            border: 0.75pt solid #808080;
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        table.data td {
            padding: 6px 9px;
            border: 0.75pt solid #b0b0b0;
            font-size: 11pt;
        }

        table.data tbody tr.zebra td {
            background-color: #f7f7f7;
        }

        table.data tfoot td {
            padding: 8px 9px;
            border: 0.75pt solid #808080;
            border-top: 1.2pt solid #000;
            background-color: #ededed;
            font-weight: bold;
            font-size: 11pt;
        }

        table.data.compact th {
            font-size: 9pt;
            padding: 5px 7px;
        }

        table.data.compact td {
            font-size: 10pt;
            padding: 4.5px 7px;
        }

        table.data.compact tfoot td {
            font-size: 10pt;
            padding: 6px 7px;
        }

        /* ===== RINGKASAN SALDO ===== */
        table.summary td {
            padding: 8px 10px;
            border: 0.75pt solid #b0b0b0;
            font-size: 11.5pt;
        }

        table.summary tr.grand td {
            border: 1.2pt solid #000;
            background-color: #ededed;
            font-weight: bold;
            font-size: 13pt;
            padding: 10px;
        }

        .col-no {
            width: 34px;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .muted {
            color: #555;
            font-size: 10pt;
        }

        .tag {
            font-size: 8.5pt;
            color: #333;
            white-space: nowrap;
        }

        .empty-row td {
            text-align: center;
            color: #555;
            font-style: italic;
            padding: 14px;
        }

        .note-box {
            border: 0.75pt solid #b0b0b0;
            background-color: #f7f7f7;
            padding: 8px 10px;
            font-size: 9.5pt;
            color: #333;
            margin-top: 10px;
        }

        /* ===== TANDA TANGAN ===== */
        .signature-section {
            page-break-inside: avoid;
            margin-top: 34px;
        }

        .signature-place {
            text-align: right;
            font-size: 11pt;
            margin-bottom: 10px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 16px;
            border: none;
            font-size: 11pt;
        }

        .signature-box {
            height: 68px;
        }

        .signature-box img {
            height: 62px;
            width: auto;
        }

        .signature-name {
            font-weight: bold;
            border-bottom: 0.75pt solid #000;
            display: inline-block;
            padding: 0 6px 1px;
        }

        .signature-role {
            font-size: 10pt;
            color: #333;
            margin-top: 3px;
        }
    </style>
</head>

<body>
    {{-- KOP SURAT --}}
    <div class="kop">
        <table>
            <tr>
                <td class="kop-logo">
                    <img src="{{ public_path('logort.png') }}" alt="Logo RT-44">
                </td>
                <td class="kop-text">
                    <h1>RUKUN TETANGGA 44</h1>
                    <h2>Perumahan Sepinggan Baru &mdash; Gading City, Balikpapan</h2>
                </td>
            </tr>
        </table>
        <div class="kop-rule"></div>
        <div class="kop-rule-thin"></div>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <table>
            <tr>
                <td>Laporan Keuangan {{ $report['blok_label'] }} &mdash; Periode {{ $report['period_label'] }}</td>
                <td class="text-right">Halaman <span class="page-num"></span></td>
            </tr>
        </table>
    </div>

    {{-- JUDUL DOKUMEN --}}
    <div class="doc-title">
        <div class="t1">Laporan Keuangan Bulanan</div>
        <div class="t2">
            @if($isBlokView) {{ $report['blok_label'] }} &mdash; @endif
            Periode {{ $report['period_label'] }}
        </div>
        @if($isBlokView)
            <div class="t3">{{ $report['house_count'] }} rumah dari total {{ $report['total_houses'] }} rumah RT-44 ({{ number_format($report['share_percent'], 1, ',', '.') }}%)</div>
        @endif
    </div>

    {{-- A. RINGKASAN --}}
    @if($isBlokView)
        <div class="section-title" style="margin-top: 0;">A. Ringkasan {{ $report['blok_label'] }}</div>
        <table class="data summary">
            <tr>
                <td>Total Pemasukan (Iuran Warga {{ $report['blok_label'] }})</td>
                <td class="text-right font-bold" style="width: 40%;">{{ $formatCurrency($report['total_income']) }}</td>
            </tr>
            <tr>
                <td>Pengeluaran Khusus {{ $report['blok_label'] }}</td>
                <td class="text-right font-bold">{{ $report['expenses_direct'] > 0 ? '- ' : '' }}{{ $formatCurrency($report['expenses_direct']) }}</td>
            </tr>
            <tr>
                <td>Porsi Pengeluaran Bersama ({{ number_format($report['share_percent'], 1, ',', '.') }}%)</td>
                <td class="text-right font-bold">{{ $report['expenses_shared'] > 0 ? '- ' : '' }}{{ $formatCurrency($report['expenses_shared']) }}</td>
            </tr>
            <tr class="grand">
                <td>SELISIH {{ strtoupper($report['blok_label']) }}</td>
                <td class="text-right">{{ $report['net'] >= 0 ? '' : '- ' }}{{ $formatCurrency(abs($report['net'])) }}</td>
            </tr>
        </table>
        <div class="note-box">
            Kas RT-44 tetap satu. Laporan ini memisahkan pemasukan dan pengeluaran
            {{ $report['blok_label'] }} saja, sehingga yang ditampilkan adalah selisih blok,
            bukan saldo kas. Saldo kas RT selengkapnya ada di laporan gabungan.
        </div>
    @else
        <div class="section-title" style="margin-top: 0;">A. Ringkasan Kas</div>
        <table class="data summary">
            <tr>
                <td>Saldo Awal Periode</td>
                <td class="text-right font-bold" style="width: 40%;">{{ $formatCurrency($report['saldo_awal']) }}</td>
            </tr>
            <tr>
                <td>Total Pemasukan (Iuran Warga)</td>
                <td class="text-right font-bold">+ {{ $formatCurrency($report['total_income']) }}</td>
            </tr>
            <tr>
                <td>Total Pengeluaran</td>
                <td class="text-right font-bold">- {{ $formatCurrency($report['total_expenses']) }}</td>
            </tr>
            <tr class="grand">
                <td>SALDO AKHIR KAS</td>
                <td class="text-right">{{ $formatCurrency($report['saldo_akhir']) }}</td>
            </tr>
        </table>
    @endif

    {{-- B. RINCIAN PEMASUKAN --}}
    <div class="section-title">B. Rincian Pemasukan</div>
    <p class="section-note">
        Dihitung dari pembayaran warga{{ $isBlokView ? ' ' . $report['blok_label'] : '' }} yang sudah diverifikasi pada periode ini.
    </p>
    <table class="data">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th>Sumber Pemasukan</th>
                <th class="text-right" style="width: 32%;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="col-no">1</td>
                <td>Iuran Wajib</td>
                <td class="text-right">{{ $formatCurrency($report['income_wajib']) }}</td>
            </tr>
            <tr class="zebra">
                <td class="col-no">2</td>
                <td>Iuran Sukarela</td>
                <td class="text-right">{{ $formatCurrency($report['income_sukarela']) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total Pemasukan</td>
                <td class="text-right">{{ $formatCurrency($report['total_income']) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- C. RINCIAN PENGELUARAN --}}
    <div class="section-title">C. Rincian Pengeluaran</div>
    @if($isBlokView && $hasShared)
        <p class="section-note">
            Pengeluaran bertanda <strong>Bersama</strong> melayani seluruh RT dan dibagi menurut jumlah rumah
            ({{ $report['house_count'] }} dari {{ $report['total_houses'] }} rumah = {{ number_format($report['share_percent'], 1, ',', '.') }}%).
        </p>
    @endif
    <table class="data {{ $isBlokView ? 'compact' : '' }}">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th style="width: 78px;">Tanggal</th>
                <th>Uraian Keperluan</th>
                @if($isBlokView)
                    <th style="width: 88px;">Sifat</th>
                    <th class="text-right" style="width: 100px;">Nominal Nota</th>
                    <th class="text-right" style="width: 100px;">Porsi {{ $report['blok_label'] }}</th>
                @else
                    <th class="text-right" style="width: 22%;">Nominal</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $i => $expense)
                <tr class="{{ $i % 2 === 1 ? 'zebra' : '' }}">
                    <td class="col-no">{{ $i + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($expense['date'])->isoFormat('D MMM Y') }}</td>
                    <td>
                        {{ $expense['title'] }}
                        @if(!empty($expense['category']))
                            <span class="muted">({{ $expense['category'] }})</span>
                        @endif
                        @if(!$isBlokView && $expense['blok_group'] !== 'shared')
                            <span class="tag">[Khusus Blok {{ $expense['blok_group'] }}]</span>
                        @endif
                    </td>
                    @if($isBlokView)
                        <td class="tag">
                            {{ $expense['is_shared'] ? 'Bersama' : 'Khusus ' . $report['blok_label'] }}
                        </td>
                        <td class="text-right">{{ $formatCurrency($expense['amount_full']) }}</td>
                        <td class="text-right font-bold">{{ $formatCurrency($expense['amount']) }}</td>
                    @else
                        <td class="text-right">{{ $formatCurrency($expense['amount']) }}</td>
                    @endif
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="{{ $isBlokView ? 6 : 4 }}">Tidak ada pengeluaran pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($expenses) > 0)
            <tfoot>
                <tr>
                    <td colspan="{{ $isBlokView ? 5 : 3 }}">
                        Total Pengeluaran{{ $isBlokView ? ' ' . $report['blok_label'] : '' }}
                    </td>
                    <td class="text-right">{{ $formatCurrency($report['total_expenses']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- D. RINGKASAN PER BLOK (hanya laporan gabungan) --}}
    @if(!$isBlokView && count($report['blok_summary']) > 0)
        <div class="section-title">D. Ringkasan Per Blok</div>
        <p class="section-note">
            Pemasukan dipisah menurut blok rumah pembayar. Pengeluaran bersama dibagi menurut jumlah rumah tiap blok.
        </p>
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 80px;">Blok</th>
                    <th class="text-center" style="width: 90px;">Jml Rumah</th>
                    <th class="text-right">Pemasukan</th>
                    <th class="text-right">Pengeluaran</th>
                    <th class="text-right">Selisih</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['blok_summary'] as $i => $b)
                    <tr class="{{ $i % 2 === 1 ? 'zebra' : '' }}">
                        <td class="font-bold">{{ $b['label'] }}</td>
                        <td class="text-center">{{ $b['house_count'] }}</td>
                        <td class="text-right">{{ $formatCurrency($b['income']) }}</td>
                        <td class="text-right">{{ $formatCurrency($b['expenses']) }}</td>
                        <td class="text-right font-bold">
                            {{ $b['net'] >= 0 ? '' : '- ' }}{{ $formatCurrency(abs($b['net'])) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="text-center">{{ $report['total_houses'] }}</td>
                    <td class="text-right">{{ $formatCurrency($report['total_income']) }}</td>
                    <td class="text-right">{{ $formatCurrency($report['total_expenses']) }}</td>
                    <td class="text-right">
                        {{ $report['net'] >= 0 ? '' : '- ' }}{{ $formatCurrency(abs($report['net'])) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    @endif

    {{-- TANDA TANGAN --}}
    <div class="signature-section">
        <div class="signature-place">
            Balikpapan, {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}
        </div>
        <table class="signature-table">
            <tr>
                <td>
                    <div>Mengetahui,</div>
                    <div class="signature-box">
                        <img src="{{ public_path('images/ttd-ketuart.png') }}" alt="TTD Ketua RT">
                    </div>
                    <div class="signature-name">Aidil Saputra Kirsan</div>
                    <div class="signature-role">Ketua RT-44</div>
                </td>
                <td>
                    <div>Dibuat oleh,</div>
                    <div class="signature-box">
                        <img src="{{ public_path('images/ttd-bendahara.png') }}" alt="TTD Bendahara">
                    </div>
                    <div class="signature-name">Dios Andri Baskoro</div>
                    <div class="signature-role">Bendahara RT-44</div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
