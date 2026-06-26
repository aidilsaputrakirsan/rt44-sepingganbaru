<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan RT-44</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* dompdf mengabaikan @page margin (terutama kiri/kanan), tapi konsisten
           menerapkan margin pada <body> di semua halaman termasuk lanjutan. */
        @page {
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 16mm 14mm;
        }

        /* ── Header ── */
        .header {
            width: 100%;
            margin-bottom: 14px;
        }
        .header-table { width: 100%; }
        .header-table td { vertical-align: middle; }
        .header-logo { width: 90px; text-align: center; }
        .header-logo img { width: 78px; height: 78px; }
        .header-text { text-align: center; }
        .header-text .title {
            font-size: 17px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .header-text .sub1 {
            font-size: 12px;
            font-weight: bold;
            margin-top: 2px;
        }
        .header-text .sub2 {
            font-size: 11.5px;
            margin-top: 2px;
        }
        .header-text .bulan {
            font-size: 12px;
            font-weight: bold;
            margin-top: 6px;
        }
        .header-logo-right { width: 90px; text-align: center; }
        .header-logo-right img { width: 78px; height: 78px; }

        /* ── Tabel ── */
        table.report {
            width: 100%;
            border-collapse: collapse;
        }
        table.report th,
        table.report td {
            border: 1px solid #555;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.report thead th {
            background: #1f4e9b;
            color: #fff;
            text-align: center;
            font-size: 11px;
            text-transform: uppercase;
            padding: 7px 6px;
        }
        .col-no    { width: 5%;  text-align: center; }
        .col-tgl   { width: 14%; text-align: center; }
        .col-uraian{ width: 51%; text-align: justify; line-height: 1.45; }
        .col-dok   { width: 30%; text-align: center; vertical-align: middle; }

        .no-surat { font-size: 11px; }
        .no-surat .label { display: block; }
        .dok-photo { margin: 0 auto; }
        .dok-photo.landscape { width: 210px; }
        .dok-photo.portrait  { height: 220px; }

        tr { page-break-inside: avoid; }

        /* ── Footer TTD ── */
        .ttd {
            width: 100%;
            margin-top: 26px;
        }
        .ttd-table { width: 100%; }
        .ttd-table td {
            width: 50%;
            vertical-align: top;
            font-size: 11px;
        }
        .ttd-left { text-align: left; }
        .ttd-right { text-align: right; }
        .ttd-role { font-weight: bold; }
        .ttd-gap { height: 70px; }
        .ttd-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- ══ HEADER ══ -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    @if($logo_path && file_exists($logo_path))
                        <img src="{{ $logo_path }}" alt="Logo">
                    @endif
                </td>
                <td class="header-text">
                    <div class="title">LAPORAN BULANAN</div>
                    <div class="sub1">PELAKSANAAN TUGAS DAN FUNGSI KETUA RT. 44</div>
                    <div class="sub2">KELURAHAN SEPINGGAN BARU</div>
                    <div class="sub2">KECAMATAN BALIKPAPAN SELATAN</div>
                    <div class="bulan">BULAN : {{ $bulan_label }}</div>
                </td>
                <td class="header-logo-right">
                    @if($logo_rt_path && file_exists($logo_rt_path))
                        <img src="{{ $logo_rt_path }}" alt="Logo RT-44">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- ══ TABEL KEGIATAN ══ -->
    <table class="report">
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th class="col-tgl">Tanggal</th>
                <th class="col-uraian">Uraian Kegiatan</th>
                <th class="col-dok">Dokumentasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $i => $a)
                <tr>
                    <td class="col-no">{{ $i + 1 }}</td>
                    <td class="col-tgl">{{ $a['tanggal'] }}</td>
                    <td class="col-uraian">{{ $a['uraian'] }}</td>
                    <td class="col-dok">
                        @if($a['no_surat'])
                            <span class="no-surat">
                                <span class="label">No Surat:</span>
                                {{ $a['no_surat'] }}
                            </span>
                        @endif
                        @if($a['img_path'])
                            <img
                                src="{{ $a['img_path'] }}"
                                class="dok-photo {{ $a['orientation'] === 'portrait' ? 'portrait' : 'landscape' }}"
                                alt="Dokumentasi"
                            >
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center; padding:20px; color:#777;">
                        Belum ada kegiatan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- ══ TTD ══ -->
    <div class="ttd">
        <table class="ttd-table">
            <tr>
                <td class="ttd-left">
                    <div>Mengetahui,</div>
                    <div class="ttd-role">LURAH SEPINGGAN BARU</div>
                </td>
                <td class="ttd-right">
                    <div>Balikpapan, {{ $tanggal_pengesahan }}</div>
                    <div class="ttd-role">KETUA RT. 44</div>
                </td>
            </tr>
            <tr>
                <td colspan="2"><div class="ttd-gap"></div></td>
            </tr>
            <tr>
                <td class="ttd-left">
                    <span class="ttd-name">{{ $lurah_name }}</span>
                </td>
                <td class="ttd-right">
                    <span class="ttd-name">{{ $ketua_name }}</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
