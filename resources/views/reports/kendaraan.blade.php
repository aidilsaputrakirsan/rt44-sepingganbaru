<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Data Kendaraan Warga RT-44</title>
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
            font-size: 17px;
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

        .header-date {
            text-align: right;
            font-size: 9px;
            color: #94a3b8;
        }

        /* === RINGKASAN === */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .summary-table td {
            width: 25%;
            text-align: center;
            border: 1px solid #fde68a;
            background-color: #fffbeb;
            padding: 8px 4px;
        }

        .summary-value {
            font-size: 15px;
            font-weight: bold;
            color: #92400e;
        }

        .summary-label {
            font-size: 8px;
            color: #78350f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* === TABLE === */
        table.detail {
            width: 100%;
            border-collapse: collapse;
        }

        table.detail th {
            background-color: #fef3c7;
            text-align: left;
            padding: 7px 8px;
            border-bottom: 2px solid #fde68a;
            color: #92400e;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5px;
            letter-spacing: 0.5px;
        }

        table.detail td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10.5px;
        }

        table.detail tr:nth-child(even) {
            background-color: #fefce8;
        }

        .text-center {
            text-align: center;
        }

        .checkbox {
            width: 13px;
            height: 13px;
            border: 1.5px solid #78350f;
            display: inline-block;
        }
    </style>
</head>

<body>
    <div class="header">
        <table>
            <tr>
                <td class="header-logo">
                    <img src="{{ public_path('logort.png') }}" alt="Logo RT-44">
                </td>
                <td class="header-text">
                    <h1>DATA KENDARAAN WARGA RT-44</h1>
                    <h2>Perumahan Sepinggan Baru &mdash; Gading City, Balikpapan</h2>
                </td>
                <td class="header-date">
                    Dicetak: {{ $generatedAt }}
                </td>
            </tr>
        </table>
    </div>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-value">{{ $ringkasan['total_rumah'] }}</div>
                <div class="summary-label">Total Rumah</div>
            </td>
            <td>
                <div class="summary-value">{{ $ringkasan['sudah_isi'] }} / {{ $ringkasan['total_rumah'] }}</div>
                <div class="summary-label">Sudah Mengisi</div>
            </td>
            <td>
                <div class="summary-value">{{ $ringkasan['total_mobil'] }}</div>
                <div class="summary-label">Total Mobil</div>
            </td>
            <td>
                <div class="summary-value">{{ $ringkasan['total_motor'] }}</div>
                <div class="summary-label">Total Motor</div>
            </td>
        </tr>
    </table>

    <table class="detail">
        <thead>
            <tr>
                <th style="width: 28px;" class="text-center">No</th>
                <th>Rumah</th>
                <th>Nama</th>
                <th style="width: 45px;" class="text-center">Mobil</th>
                <th style="width: 45px;" class="text-center">Motor</th>
                <th style="width: 55px;" class="text-center">Checklist</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($houses as $h)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $h['rumah'] }}</td>
                    <td>{{ $h['nama'] }}</td>
                    <td class="text-center">{{ $h['jumlah_mobil'] }}</td>
                    <td class="text-center">{{ $h['jumlah_motor'] }}</td>
                    <td class="text-center"><span class="checkbox"></span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
