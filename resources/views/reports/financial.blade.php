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
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.4;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
        }

        .header h1 {
            color: #1e1b4b;
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }

        .header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 14px;
            letter-spacing: 1px;
        }

        .section-title {
            background-color: #f1f5f9;
            padding: 8px 12px;
            font-weight: bold;
            color: #1e1b4b;
            margin-top: 20px;
            margin-bottom: 10px;
            border-left: 4px solid #4f46e5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #f8fafc;
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .summary-card {
            background-color: #1e1b4b;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .summary-grid {
            width: 100%;
        }

        .summary-item {
            padding: 10px 0;
        }

        .label {
            color: #94a3b8;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .value {
            font-size: 16px;
            font-weight: bold;
        }

        .total-row {
            border-top: 2px solid #e2e8f0;
            background-color: #f8fafc;
        }

        .income-text {
            color: #16a34a;
        }

        .expense-text {
            color: #dc2626;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            padding: 10px 0;
        }

        .signature-container {
            margin-top: 50px;
            width: 100%;
        }

        .signature-box {
            width: 45%;
            display: inline-block;
            text-align: center;
        }

        .signature-space {
            height: 60px;
            margin: 10px 0;
            color: #e2e8f0;
            font-size: 8px;
            line-height: 60px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Laporan Keuangan RT-44</h1>
        <p>Periode: {{ $report['period_label'] }}</p>
    </div>

    <div class="section-title">Ringkasan Saldo</div>
    <table>
        <tr>
            <td>Saldo Awal Periode</td>
            <td class="text-right font-bold">{{ $formatCurrency($report['saldo_awal']) }}</td>
        </tr>
        <tr>
            <td class="income-text">Total Pemasukan (Iuran)</td>
            <td class="text-right font-bold income-text">+ {{ $formatCurrency($report['total_income']) }}</td>
        </tr>
        <tr>
            <td class="expense-text">Total Pengeluaran</td>
            <td class="text-right font-bold expense-text">- {{ $formatCurrency($report['total_expenses']) }}</td>
        </tr>
        <tr class="total-row">
            <td class="font-bold">Saldo Akhir Kas</td>
            <td class="text-right font-bold" style="font-size: 16px; color: #16a34a;">
                {{ $formatCurrency($report['saldo_akhir']) }}
            </td>
        </tr>
    </table>

    <div class="section-title">Rincian Pemasukan</div>
    <table>
        <thead>
            <tr>
                <th>Sumber Pemasukan</th>
                <th class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Iuran Wajib</td>
                <td class="text-right">{{ $formatCurrency($report['income_wajib']) }}</td>
            </tr>
            <tr>
                <td>Iuran Sukarela</td>
                <td class="text-right">{{ $formatCurrency($report['income_sukarela']) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="font-bold">
                <td>Total Pemasukan</td>
                <td class="text-right income-text">{{ $formatCurrency($report['total_income']) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="section-title">Rincian Pengeluaran</div>
    <table>
        <thead>
            <tr>
                <th>Deskripsi Kegiatan / Keperluan</th>
                <th class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['expenses'] as $expense)
                <tr>
                    <td>{{ $expense->title }}</td>
                    <td class="text-right expense-text">{{ $formatCurrency($expense->amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" style="text-align: center; color: #94a3b8; font-style: italic;">Tidak ada catatan
                        pengeluaran.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($report['expenses']) > 0)
            <tfoot>
                <tr class="font-bold">
                    <td>Total Pengeluaran</td>
                    <td class="text-right expense-text">{{ $formatCurrency($report['total_expenses']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div style="margin-top: 40px;">
        <p style="text-align: center; margin-bottom: 20px;">Mengetahui,</p>
        <div class="signature-container">
            <div class="signature-box" style="float: left;">
                <p class="font-bold">Ketua RT.44</p>
                <div class="signature-space" style="color: #000; font-weight: bold; font-size: 10px;">( TTD )</div>
                <p class="font-bold">Aidil Saputra Kirsan</p>
            </div>
            <div class="signature-box" style="float: right;">
                <p class="font-bold">Bendahara RT.44</p>
                <div class="signature-space" style="color: #000; font-weight: bold; font-size: 10px;">( TTD )</div>
                <p class="font-bold">Dios Andri Baskoro</p>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>

    <div class="footer">
        Dicetak otomatis oleh Sistem Manajemen RT-44 pada {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}
    </div>
</body>

</html>