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
            margin: 22mm 10mm 10mm 10mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            font-size: 8px;
            margin: 0;
            padding: 0;
        }

        /* === HEADER / KOP (fixed top every page) === */
        .header {
            position: fixed;
            top: -14mm;
            left: 0;
            right: 0;
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 6px;
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
            width: 45px;
        }

        .header-logo img {
            width: 40px;
            height: 40px;
        }

        .header-text {
            padding-left: 8px;
        }

        .header-text h1 {
            margin: 0;
            font-size: 16px;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .header-text h2 {
            margin: 1px 0 0;
            font-size: 9px;
            color: #64748b;
            font-weight: normal;
        }

        .header-year {
            text-align: right;
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
        }

        /* === TABLE === */
        table.calendar {
            width: 100%;
            border-collapse: collapse;
        }

        table.calendar th {
            background-color: #92400e;
            color: white;
            padding: 5px 3px;
            text-align: center;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        table.calendar th:first-child {
            text-align: left;
            padding-left: 6px;
            width: 65px;
        }

        table.calendar td {
            padding: 3px 2px;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
            font-size: 7px;
        }

        table.calendar td:first-child {
            text-align: left;
            font-weight: bold;
            font-size: 7.5px;
            white-space: nowrap;
            padding-left: 6px;
        }

        table.calendar tr:nth-child(even) {
            background-color: #fffbeb;
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

        /* === LEGEND === */
        .legend {
            margin-top: 10px;
            font-size: 7.5px;
            color: #64748b;
        }

        .legend span {
            display: inline-block;
            margin-right: 14px;
        }

        .legend-box {
            display: inline-block;
            width: 9px;
            height: 9px;
            margin-right: 3px;
            vertical-align: middle;
            border: 1px solid #e2e8f0;
        }

    </style>
</head>

<body>
    <!-- KOP -->
    <div class="header">
        <table>
            <tr>
                <td class="header-logo">
                    <img src="{{ public_path('logort.png') }}" alt="Logo RT-44">
                </td>
                <td class="header-text">
                    <h1>KALENDER IURAN RT-44</h1>
                    <h2>Perumahan Sepinggan Baru &mdash; Gading City, Balikpapan</h2>
                </td>
                <td class="header-year">{{ $year }}</td>
            </tr>
        </table>
    </div>

    <table class="calendar">
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

</body>

</html>