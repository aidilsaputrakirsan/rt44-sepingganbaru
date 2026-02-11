@php
    $formatCurrency = function ($amount) {
        if ($amount == 0) return '-';
        return number_format($amount / 1000, 0, ',', '.') . 'k';
    };
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Kalender Iuran RT-44 - Tahun {{ $year }}</title>
    <style>
        @page {
            size: landscape;
            margin: 15mm 10mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 8px;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
        }

        .header h1 {
            color: #1e1b4b;
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }

        .header p {
            margin: 3px 0 0;
            color: #64748b;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #1e1b4b;
            color: white;
            padding: 6px 4px;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        th:first-child {
            text-align: left;
            width: 70px;
        }

        td {
            padding: 4px 3px;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
            font-size: 7px;
        }

        td:first-child {
            text-align: left;
            font-weight: bold;
            font-size: 8px;
            white-space: nowrap;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .paid {
            background-color: #dcfce7;
            color: #166534;
            font-weight: bold;
        }

        .partial {
            background-color: #fef9c3;
            color: #854d0e;
            font-weight: bold;
        }

        .unpaid {
            color: #991b1b;
        }

        .none {
            color: #d1d5db;
        }

        .legend {
            margin-top: 12px;
            font-size: 8px;
            color: #64748b;
        }

        .legend span {
            display: inline-block;
            margin-right: 15px;
        }

        .legend-box {
            display: inline-block;
            width: 10px;
            height: 10px;
            margin-right: 3px;
            vertical-align: middle;
            border: 1px solid #e2e8f0;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            padding: 5px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Kalender Iuran RT-44 Sepinggan Baru</h1>
        <p>Tahun {{ $year }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Rumah</th>
                @foreach($months as $m)
                    <th>{{ $m }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($calendar as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    @for($m = 1; $m <= 12; $m++)
                        @php
                            $data = $row['months'][$m];
                            $class = 'none';
                            $text = '-';

                            if ($data['status'] === 'paid') {
                                $class = 'paid';
                                $text = $formatCurrency($data['paid_amount']);
                            } elseif ($data['status'] === 'unpaid' || $data['status'] === 'overdue') {
                                if ($data['paid_amount'] > 0) {
                                    $class = 'partial';
                                    $text = $formatCurrency($data['paid_amount']);
                                } else {
                                    $class = 'unpaid';
                                    $text = '-';
                                }
                            }
                        @endphp
                        <td class="{{ $class }}">{{ $text }}</td>
                    @endfor
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="legend">
        <span><span class="legend-box" style="background-color: #dcfce7;"></span> Lunas</span>
        <span><span class="legend-box" style="background-color: #fef9c3;"></span> Bayar Sebagian</span>
        <span><span class="legend-box" style="background-color: white;"></span> Belum Bayar</span>
        <span><span class="legend-box" style="background-color: #f3f4f6;"></span> Tidak Ada Tagihan</span>
    </div>

    <div class="footer">
        Dicetak otomatis oleh Sistem Manajemen RT-44 pada {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}
    </div>
</body>

</html>
