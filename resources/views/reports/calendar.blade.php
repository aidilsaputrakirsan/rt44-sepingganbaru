@php
    $formatCompact = function ($amount) {
        if ($amount <= 0) return '-';
        if ($amount >= 1000000) return number_format($amount / 1000000, 1, ',', '.') . 'jt';
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
            margin: 22mm 8mm 12mm 8mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            font-size: 8px;
            margin: 0;
            padding: 0;
        }

        /* === KOP (fixed top every page) === */
        .header {
            position: fixed;
            top: -14mm;
            left: 0;
            right: 0;
            border-bottom: 2.5px solid #f59e0b;
            padding-bottom: 5px;
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
            width: 42px;
        }

        .header-logo img {
            width: 38px;
            height: 38px;
        }

        .header-text {
            padding-left: 8px;
        }

        .header-text h1 {
            margin: 0;
            font-size: 15px;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .header-text h2 {
            margin: 1px 0 0;
            font-size: 8.5px;
            color: #64748b;
            font-weight: normal;
        }

        .header-right {
            text-align: right;
        }

        .header-year {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            display: block;
        }

        .header-generated {
            font-size: 7px;
            color: #94a3b8;
            display: block;
            margin-top: 1px;
        }

        /* === FOOTER === */
        .footer {
            position: fixed;
            bottom: -6mm;
            left: 0;
            right: 0;
            border-top: 1px solid #e2e8f0;
            padding-top: 3px;
            font-size: 6.5px;
            color: #94a3b8;
        }

        .footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer td {
            border: none;
            padding: 0;
        }

        /* === TABLE === */
        table.calendar {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.calendar thead tr th {
            background-color: #92400e;
            color: white;
            padding: 5px 2px;
            text-align: center;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        table.calendar thead tr th.col-rumah {
            text-align: left;
            padding-left: 5px;
            width: 52px;
        }

        table.calendar thead tr th.col-utang {
            background-color: #7f1d1d;
            width: 38px;
        }

        table.calendar td {
            padding: 3px 1px;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
            font-size: 6.5px;
            overflow: hidden;
        }

        table.calendar td.col-rumah {
            text-align: left;
            font-weight: bold;
            font-size: 7px;
            white-space: nowrap;
            padding-left: 5px;
        }

        table.calendar td.col-utang {
            font-weight: bold;
            font-size: 6.5px;
            background-color: #fff1f2;
            color: #be123c;
            border-left: 1px solid #fecdd3;
        }

        table.calendar td.col-utang.no-debt {
            color: #86efac;
            background-color: #f0fdf4;
            font-weight: normal;
        }

        table.calendar tr:nth-child(even) td {
            background-color: #fffbeb;
        }

        table.calendar tr:nth-child(even) td.col-utang {
            background-color: #fff1f2;
        }

        table.calendar tr:nth-child(even) td.col-utang.no-debt {
            background-color: #f0fdf4;
        }

        /* === STATUS CELL COLORS === */
        .paid {
            background-color: #dcfce7 !important;
            color: #166534;
            font-weight: bold;
        }

        .paid-extra {
            background-color: #86efac !important;
            color: #14532d;
            font-weight: bold;
        }

        .partial {
            background-color: #fef9c3 !important;
            color: #854d0e;
            font-weight: bold;
        }

        .unpaid {
            color: #991b1b;
        }

        .none {
            color: #d1d5db;
        }

        .subsidi {
            background-color: #dbeafe !important;
            color: #1e40af;
            font-weight: bold;
            font-size: 5.5px;
            letter-spacing: 0.3px;
        }

        .subsidi-badge {
            display: inline-block;
            background-color: #dbeafe;
            color: #1e40af;
            font-size: 5px;
            font-weight: bold;
            padding: 0.5px 2px;
            border-radius: 2px;
            margin-left: 2px;
            letter-spacing: 0.2px;
            vertical-align: middle;
        }

        /* === TOTAL ROW === */
        tr.total-row td {
            background-color: #1e293b !important;
            color: white;
            font-weight: bold;
            font-size: 7px;
            padding: 4px 2px;
            border-bottom: none;
        }

        tr.total-row td.col-rumah {
            padding-left: 5px;
            font-size: 7px;
            white-space: normal;
            word-break: break-word;
        }

        tr.total-row td.col-utang {
            background-color: #7f1d1d !important;
            color: #fca5a5;
            font-size: 7px;
            border-left: 1px solid #991b1b;
        }

        /* === LEGEND === */
        .footer-legend {
            font-size: 6.5px;
            color: #64748b;
        }

        .footer-legend span {
            display: inline-block;
            margin-right: 10px;
        }

        .legend-box {
            display: inline-block;
            width: 8px;
            height: 8px;
            margin-right: 2px;
            vertical-align: middle;
            border: 1px solid #e2e8f0;
        }

        .footer-note {
            font-size: 6px;
            color: #94a3b8;
            margin-top: 3px;
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
                <td class="header-right">
                    <span class="header-year">{{ $year }}</span>
                    <span class="header-generated">Dicetak: {{ $generatedAt }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <table>
            <tr>
                <td class="footer-legend">
                    <span><span class="legend-box" style="background-color:#dcfce7;"></span> Lunas</span>
                    <span><span class="legend-box" style="background-color:#86efac;"></span> Lunas + Sukarela</span>
                    <span><span class="legend-box" style="background-color:#fef9c3;"></span> Bayar Sebagian</span>
                    <span><span class="legend-box" style="background-color:white;"></span> Belum Bayar</span>
                    <span><span class="legend-box" style="background-color:#dbeafe;"></span> Subsidi</span>
                    <span><span class="legend-box" style="background-color:#f3f4f6;"></span> Tidak Ada Tagihan</span>
                    <span style="margin-left:6px; color:#be123c; font-weight:bold;">■</span>
                    <span style="color:#be123c;"> Kolom Utang = saldo belum lunas s.d. bulan ini (dihitung mulai bulan pertama ada pembayaran)</span>
                </td>
                <td style="text-align:right; color:#94a3b8; font-size:6px; white-space:nowrap;">
                    RT-44 Sepinggan Baru &bull; {{ $year }}
                </td>
            </tr>
        </table>
    </div>

    <table class="calendar">
        <thead>
            <tr>
                <th class="col-rumah">Rumah</th>
                @foreach($months as $m)
                    <th>{{ $m }}</th>
                @endforeach
                <th class="col-utang">Utang</th>
            </tr>
        </thead>
        <tbody>
            @foreach($calendar as $row)
                <tr>
                    <td class="col-rumah">
                        {{ $row['name'] }}
                        @if($row['is_subsidized'])
                            <span class="subsidi-badge">SUBSIDI</span>
                        @endif
                    </td>
                    @for($m = 1; $m <= 12; $m++)
                        @php
                            $data  = $row['months'][$m];
                            $class = 'none';
                            $text  = '-';

                            if ($data['status'] === 'paid') {
                                if ($data['paid_amount'] > $data['bill_amount'] && $data['bill_amount'] > 0) {
                                    $class = 'paid-extra';
                                    $text  = '+' . $formatCompact($data['paid_amount']);
                                } else {
                                    $class = 'paid';
                                    $text  = $formatCompact($data['paid_amount']);
                                }
                            } elseif ($data['status'] === 'unpaid' || $data['status'] === 'overdue') {
                                if ($data['paid_amount'] > 0) {
                                    $class = 'partial';
                                    $text  = $formatCompact($data['paid_amount']);
                                } else {
                                    $class = 'unpaid';
                                    $text  = '-';
                                }
                            } elseif ($data['status'] === 'none' && $row['is_subsidized']) {
                                $class = 'subsidi';
                                $text  = 'S';
                            }
                        @endphp
                        <td class="{{ $class }}">{{ $text }}</td>
                    @endfor
                    <td class="col-utang {{ $row['total_debt'] <= 0 ? 'no-debt' : '' }}">
                        {{ $row['total_debt'] > 0 ? $formatCompact($row['total_debt']) : 'OK' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td class="col-rumah">TOTAL UTANG WARGA</td>
                @for($m = 1; $m <= 12; $m++)
                    <td></td>
                @endfor
                <td class="col-utang">
                    {{ $totalDebtAll > 0 ? $formatCompact($totalDebtAll) : '-' }}
                </td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
