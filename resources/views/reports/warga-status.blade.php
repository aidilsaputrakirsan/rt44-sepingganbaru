<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Status Rumah RT-44</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #1e293b;
            background: #fff;
            padding: 24px 28px;
        }

        /* ── Header ── */
        .header {
            text-align: center;
            border-bottom: 3px solid #1e3a5f;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .header-inner {
            display: inline-block;
            vertical-align: middle;
        }
        .logo {
            width: 56px;
            height: 56px;
            display: inline-block;
            vertical-align: middle;
            margin-right: 14px;
        }
        .title-block {
            display: inline-block;
            vertical-align: middle;
            text-align: left;
        }
        .title-main {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .title-sub {
            font-size: 12px;
            color: #475569;
            margin-top: 2px;
        }
        .title-date {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* ── Petunjuk ── */
        .petunjuk {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 8px 12px;
            margin-bottom: 16px;
            font-size: 12px;
            color: #0369a1;
        }
        .petunjuk strong { font-size: 13px; }

        /* ── Legend ── */
        .legend {
            margin-bottom: 16px;
            font-size: 12px;
        }
        .legend-item {
            display: inline-block;
            margin-right: 20px;
            vertical-align: middle;
        }
        .dot {
            display: inline-block;
            width: 14px;
            height: 14px;
            border-radius: 3px;
            vertical-align: middle;
            margin-right: 5px;
        }
        .dot-huni  { background: #16a34a; }
        .dot-kosong { background: #94a3b8; }

        /* ── Blok Section ── */
        .blok-section { margin-bottom: 20px; }

        .blok-title {
            background: #1e3a5f;
            color: #fff;
            font-size: 13px;
            font-weight: bold;
            padding: 5px 10px;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        /* ── Grid rumah ── */
        .grid-rumah {
            width: 100%;
            border-collapse: collapse;
        }
        .grid-rumah td {
            width: 20%;
            padding: 5px 4px;
            vertical-align: top;
        }

        .rumah-card {
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 6px 8px;
            text-align: center;
        }
        .rumah-card.huni {
            border-left: 4px solid #16a34a;
            background: #f0fdf4;
        }
        .rumah-card.kosong {
            border-left: 4px solid #94a3b8;
            background: #f8fafc;
        }
        .rumah-no {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
        }
        .rumah-status {
            font-size: 11px;
            font-weight: bold;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .rumah-status.huni   { color: #15803d; }
        .rumah-status.kosong { color: #64748b; }

        /* ── Ringkasan ── */
        .summary {
            margin-top: 20px;
            border-top: 2px solid #e2e8f0;
            padding-top: 12px;
        }
        .summary-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #1e3a5f;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .summary-table th {
            background: #f1f5f9;
            padding: 6px 10px;
            text-align: left;
            border: 1px solid #e2e8f0;
            font-weight: bold;
        }
        .summary-table td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .summary-table td.label { text-align: left; font-weight: bold; }
        .summary-table tr.total-row { background: #f8fafc; font-weight: bold; }

        .footer {
            margin-top: 18px;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    {{-- ── HEADER ── --}}
    <div class="header">
        <img src="{{ public_path('logort.png') }}" class="logo" alt="Logo RT-44">
        <div class="title-block">
            <div class="title-main">Daftar Status Rumah RT-44</div>
            <div class="title-sub">Perumahan Sepinggan Baru, Balikpapan &mdash; untuk Petugas Kebersihan</div>
            <div class="title-date">Dicetak: {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</div>
        </div>
    </div>

    {{-- ── PETUNJUK ── --}}
    <div class="petunjuk">
        <strong>Petunjuk:</strong>
        Rumah berlabel <strong>BERPENGHUNI</strong> = ada penghuninya, sampah diambil seperti biasa.
        Rumah berlabel <strong>KOSONG</strong> = tidak berpenghuni, bisa dilewati jika tidak ada sampah.
    </div>

    {{-- ── LEGEND ── --}}
    <div class="legend">
        <span class="legend-item"><span class="dot dot-huni"></span> Berpenghuni</span>
        <span class="legend-item"><span class="dot dot-kosong"></span> Kosong</span>
    </div>

    {{-- ── DAFTAR PER BLOK ── --}}
    @foreach ($grouped as $prefix => $houses)
        <div class="blok-section">
            <div class="blok-title">
                BLOK {{ $prefix }}
                &nbsp;&mdash;&nbsp; {{ $houses->count() }} Rumah
                &nbsp;|&nbsp; {{ $houses->where('status_huni','berpenghuni')->count() }} Berpenghuni
                &nbsp;|&nbsp; {{ $houses->where('status_huni','kosong')->count() }} Kosong
            </div>

            {{-- Grid 5 kolom --}}
            @php $chunks = $houses->chunk(5); @endphp
            <table class="grid-rumah">
                @foreach ($chunks as $row)
                    <tr>
                        @foreach ($row as $house)
                            <td>
                                <div class="rumah-card {{ $house->status_huni === 'berpenghuni' ? 'huni' : 'kosong' }}">
                                    <div class="rumah-no">{{ $house->blok }}/{{ $house->nomor }}</div>
                                    <div class="rumah-status {{ $house->status_huni === 'berpenghuni' ? 'huni' : 'kosong' }}">
                                        {{ $house->status_huni === 'berpenghuni' ? 'Berpenghuni' : 'Kosong' }}
                                    </div>
                                </div>
                            </td>
                        @endforeach
                        {{-- Isi sisa kolom kosong agar layout rapi --}}
                        @for ($i = $row->count(); $i < 5; $i++)
                            <td></td>
                        @endfor
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach

    {{-- ── RINGKASAN ── --}}
    <div class="summary">
        <div class="summary-title">Ringkasan</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Blok</th>
                    <th>Total Rumah</th>
                    <th>Berpenghuni</th>
                    <th>Kosong</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($grouped as $prefix => $houses)
                    <tr>
                        <td class="label">Blok {{ $prefix }}</td>
                        <td>{{ $houses->count() }}</td>
                        <td style="color:#15803d;font-weight:bold;">{{ $houses->where('status_huni','berpenghuni')->count() }}</td>
                        <td style="color:#64748b;">{{ $houses->where('status_huni','kosong')->count() }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td class="label">TOTAL</td>
                    <td>{{ $allHouses->count() }}</td>
                    <td style="color:#15803d;font-weight:bold;">{{ $allHouses->where('status_huni','berpenghuni')->count() }}</td>
                    <td style="color:#64748b;">{{ $allHouses->where('status_huni','kosong')->count() }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        RT-44 Perumahan Sepinggan Baru &mdash; Dokumen ini digenerate otomatis oleh Sistem Manajemen RT-44
    </div>

</body>
</html>
