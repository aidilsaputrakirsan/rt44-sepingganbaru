@php
    $formatCurrency = function ($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    };
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan RT-44 - {{ $report['period_label'] }}</title>
    <style>
        @page {
            margin: 30mm 15mm 15mm 15mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            line-height: 1.5;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }

        /* === HEADER / KOP SURAT (fixed top every page) === */
        .header {
            position: fixed;
            top: -18mm;
            left: 0;
            right: 0;
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 8px;
        }

        .header table {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }

        .header-logo {
            width: 55px;
        }

        .header-logo img {
            width: 50px;
            height: 50px;
        }

        .header-text {
            padding-left: 10px;
        }

        .header-text h1 {
            margin: 0;
            font-size: 18px;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .header-text h2 {
            margin: 2px 0 0;
            font-size: 10px;
            color: #64748b;
            font-weight: normal;
            letter-spacing: 0.5px;
        }

        .header-period {
            text-align: right;
        }

        .header-period .period-label {
            font-size: 9px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header-period .period-value {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
        }

        /* === SECTIONS === */
        .section-title {
            background-color: #fffbeb;
            padding: 8px 12px;
            font-weight: bold;
            color: #92400e;
            margin-top: 22px;
            margin-bottom: 8px;
            border-left: 4px solid #f59e0b;
            font-size: 12px;
        }

        /* === RINGKASAN SALDO === */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #fde68a;
            margin-bottom: 20px;
        }

        .summary-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #fef3c7;
            font-size: 12px;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-total {
            background-color: #fffbeb;
        }

        .summary-total td {
            padding: 12px 14px;
            font-size: 13px;
        }

        /* === TABLE === */
        table.detail {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        table.detail th {
            background-color: #fef3c7;
            text-align: left;
            padding: 8px 10px;
            border-bottom: 2px solid #fde68a;
            color: #92400e;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
        }

        table.detail td {
            padding: 8px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
        }

        table.detail tr:nth-child(even) {
            background-color: #fefce8;
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

        .income-text {
            color: #16a34a;
        }

        .expense-text {
            color: #dc2626;
        }

        .total-row td {
            border-top: 2px solid #e2e8f0;
            background-color: #f8fafc;
            font-weight: bold;
            padding: 10px;
        }

        .saldo-value {
            font-size: 18px;
            color: #d97706;
            font-weight: bold;
        }

        /* === TTD === */
        .signature-section {
            page-break-inside: avoid;
            margin-top: 40px;
        }

        .signature-section p.mengetahui {
            text-align: center;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
            border: none;
        }

        .signature-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 5px;
        }

        .signature-space {
            height: 70px;
        }

        .signature-name {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #0f172a;
            display: inline-block;
            padding-top: 4px;
        }
    </style>
</head>

<body>
    <!-- KOP SURAT (fixed di atas setiap halaman) -->
    <div class="header">
        <table>
            <tr>
                <td class="header-logo">
                    <img src="{{ public_path('logort.png') }}" alt="Logo RT-44">
                </td>
                <td class="header-text">
                    <h1>LAPORAN KEUANGAN RT-44</h1>
                    <h2>Perumahan Sepinggan Baru &mdash; Gading City, Balikpapan</h2>
                </td>
                <td class="header-period">
                    <div class="period-label">Periode</div>
                    <div class="period-value">{{ $report['period_label'] }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- KONTEN -->
    <div class="section-title" style="margin-top: 0;">Ringkasan Saldo</div>
    <table class="summary-table">
        <tr>
            <td>Saldo Awal Periode</td>
            <td class="text-right font-bold" style="font-size: 13px;">{{ $formatCurrency($report['saldo_awal']) }}</td>
        </tr>
        <tr>
            <td class="income-text">Total Pemasukan (Iuran)</td>
            <td class="text-right font-bold income-text" style="font-size: 13px;">+ {{ $formatCurrency($report['total_income']) }}</td>
        </tr>
        <tr>
            <td class="expense-text">Total Pengeluaran</td>
            <td class="text-right font-bold expense-text" style="font-size: 13px;">- {{ $formatCurrency($report['total_expenses']) }}</td>
        </tr>
        <tr class="summary-total">
            <td class="font-bold" style="color: #0f172a; font-size: 13px;">Saldo Akhir Kas</td>
            <td class="text-right saldo-value">{{ $formatCurrency($report['saldo_akhir']) }}</td>
        </tr>
    </table>

    <!-- RINCIAN PEMASUKAN -->
    <div class="section-title">Rincian Pemasukan</div>
    <table class="detail">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">No</th>
                <th>Sumber Pemasukan</th>
                <th class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td>Iuran Wajib</td>
                <td class="text-right">{{ $formatCurrency($report['income_wajib']) }}</td>
            </tr>
            <tr>
                <td class="text-center">2</td>
                <td>Iuran Sukarela</td>
                <td class="text-right">{{ $formatCurrency($report['income_sukarela']) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td></td>
                <td class="font-bold">Total Pemasukan</td>
                <td class="text-right font-bold income-text">{{ $formatCurrency($report['total_income']) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- RINCIAN PENGELUARAN -->
    <div class="section-title">Rincian Pengeluaran</div>
    <table class="detail">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">No</th>
                <th>Deskripsi Kegiatan / Keperluan</th>
                <th class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['expenses'] as $expense)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $expense->title }}</td>
                    <td class="text-right expense-text">{{ $formatCurrency($expense->amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="color: #94a3b8; font-style: italic; padding: 16px;">
                        Tidak ada catatan pengeluaran pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($report['expenses']) > 0)
            <tfoot>
                <tr class="total-row">
                    <td></td>
                    <td class="font-bold">Total Pengeluaran</td>
                    <td class="text-right font-bold expense-text">{{ $formatCurrency($report['total_expenses']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- TANDA TANGAN -->
    <div class="signature-section">
        <p class="mengetahui">Mengetahui,</p>
        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-title">Ketua RT.44</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">Aidil Saputra Kirsan</div>
                </td>
                <td>
                    <div class="signature-title">Bendahara RT.44</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">Dios Andri Baskoro</div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
