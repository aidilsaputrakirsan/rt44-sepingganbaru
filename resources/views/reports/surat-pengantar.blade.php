<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Pengantar RT-44</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 12.5px;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }

        /* margin halaman di-set 0; margin asli pakai padding .sheet
           karena dompdf tidak konsisten menerapkan @page margin kiri/kanan */
        @page {
            margin: 0;
            size: A4 portrait;
        }
        .sheet {
            padding: 8mm 18mm 10mm 18mm;
        }
        /* Halaman 2 (mulai dari Jumlah Pengikut): paksa pindah halaman & beri
           margin atas normal — karena @page margin 0, padding-top jadi margin atasnya. */
        .page-two {
            page-break-before: always;
            padding-top: 15mm;
        }

        /* ── Header ── */
        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 5px;
            margin-bottom: 6px;
        }
        .header-row {
            display: table;
            width: 100%;
        }
        .header-logo {
            display: table-cell;
            width: 58px;
            vertical-align: middle;
        }
        .header-logo img {
            width: 80px;
            height: 80px;
        }
        .header-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            line-height: 1.25;
        }
        .header-text .kecamatan {
            font-size: 12px;
            font-weight: normal;
        }
        .header-text .kelurahan {
            font-size: 12px;
            font-weight: normal;
        }
        .header-text .rt-title {
            font-size: 19px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header-text .alamat {
            font-size: 9.5px;
            margin-top: 1px;
        }
        .header-spacer {
            display: table-cell;
            width: 58px;
        }

        /* ── Tanggal & Tujuan ── */
        .meta-date {
            margin-top: 8px;
            text-align: right;
            font-size: 12px;
        }
        .meta-tujuan {
            margin-top: 14px;
            text-align: left;
            font-size: 12px;
            line-height: 1.5;
        }

        /* ── Judul Surat ── */
        .surat-title {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 4px;
        }
        .surat-title .title {
            font-size: 15px;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .surat-title .nomor {
            font-size: 12px;
        }

        /* ── Body Text ── */
        .body-text {
            margin-top: 8px;
            font-size: 12.5px;
        }

        /* ── Data Table ── */
        .data-table {
            width: 100%;
            margin-top: 6px;
            border-collapse: collapse;
            font-size: 12.5px;
        }
        .data-table tr td {
            padding: 2.5px 0;
            vertical-align: bottom;
        }
        .data-table .col-label {
            width: 36%;
            padding-left: 22px;
        }
        .data-table .col-colon {
            width: 3%;
            text-align: center;
        }
        .data-table .col-value {
            width: 61%;
            border-bottom: 1px dotted #777;
            padding-bottom: 2px;
        }

        /* ── Keperluan Checkboxes ── */
        .keperluan-section {
            margin-top: 8px;
            font-size: 12.5px;
        }
        .keperluan-grid {
            display: table;
            width: 100%;
            margin-top: 2px;
        }
        .keperluan-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .keperluan-item {
            margin-bottom: 4px;
            display: block;
        }
        .checkbox-box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin-right: 6px;
            vertical-align: middle;
            text-align: center;
            line-height: 11px;
            font-size: 10px;
            font-family: 'DejaVu Sans', sans-serif;
        }
        .checkbox-checked {
            background: #000;
            color: #fff;
        }

        /* ── Alamat Dituju ── */
        .alamat-section {
            margin-top: 8px;
            font-size: 12.5px;
        }
        .alamat-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }
        .alamat-table td {
            padding: 2px 0;
            vertical-align: bottom;
        }
        .alamat-table .a-label {
            white-space: nowrap;
            padding-right: 8px;
        }
        .alamat-table .a-value {
            border-bottom: 1px dotted #777;
            padding-bottom: 2px;
        }
        .alamat-indent .a-label {
            padding-left: 55px;
            width: 90px;
        }

        /* ── Pengikut Table ── */
        .pengikut-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 12px;
        }
        .pengikut-table th, .pengikut-table td {
            border: 1px solid #000;
            padding: 2.5px 6px;
            text-align: left;
        }
        .pengikut-table th {
            text-align: center;
            font-weight: bold;
        }
        .pengikut-table .col-no { width: 8%; text-align: center; }
        .pengikut-table .col-nama { width: 62%; }
        .pengikut-table .col-hub { width: 30%; }

        /* ── Footer ── */
        .footer-section {
            margin-top: 8px;
            font-size: 12.5px;
        }
        .footer-sign {
            text-align: right;
            margin-top: 8px;
        }
        .sign-box {
            display: inline-block;
            text-align: center;
        }
        .sign-gap {
            margin-top: 100px;
        }
        .sign-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .dot-fill {
            border-bottom: 1px dotted #555;
            display: inline-block;
            min-width: 150px;
        }
    </style>
</head>
<body>
<div class="sheet">

    <!-- ══ HEADER ══ -->
    <div class="header">
        <div class="header-row">
            <div class="header-logo">
                <img src="{{ public_path('logort.png') }}" alt="Logo RT-44" />
            </div>
            <div class="header-text">
                <div class="kecamatan">KECAMATAN BALIKPAPAN SELATAN</div>
                <div class="kelurahan">KELURAHAN SEPINGGAN BARU</div>
                <div class="rt-title">Rukun Tetangga 44</div>
                <div class="alamat">JL. SEPINGGAN PRATAMA, PERUMAHAN PERMATA GADING BLOK G DAN H</div>
                <div class="alamat">HP. 085752520095</div>
            </div>
            <div class="header-spacer"></div>
        </div>
    </div>

    <!-- ══ META: Tanggal (kanan) ══ -->
    <div class="meta-date">
        Balikpapan, {{ $tanggal_surat_fmt }}
    </div>

    <!-- ══ TUJUAN (kiri) ══ -->
    <div class="meta-tujuan">
        <div>Kepada Yth.</div>
        <div>Lurah Sepinggan Baru</div>
        <div>Di-</div>
        <div style="padding-left: 20px; font-weight: bold;">Balikpapan</div>
    </div>

    <!-- ══ JUDUL ══ -->
    <div class="surat-title">
        <div class="title">Surat Pengantar</div>
        <div class="nomor">
            Nomor : {{ $nomor_surat_text ?? '........./RT.44/........./' . \Carbon\Carbon::parse($data['tanggal_surat'])->format('Y') }}
        </div>
    </div>

    <!-- ══ PEMBUKA ══ -->
    <div class="body-text">
        Surat Pengantar ini diberikan kepada&nbsp;:
    </div>

    <!-- ══ DATA IDENTITAS ══ -->
    <table class="data-table">
        <tr>
            <td class="col-label">Nama Lengkap</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ $data['nama_lengkap'] }}</td>
        </tr>
        <tr>
            <td class="col-label">Jenis Kelamin</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ $data['jenis_kelamin'] }}</td>
        </tr>
        <tr>
            <td class="col-label">Tempat/Tanggal Lahir</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ $data['tempat_lahir'] }}, {{ $tanggal_lahir_fmt }}</td>
        </tr>
        <tr>
            <td class="col-label">Status Perkawinan</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ $data['status_perkawinan'] }}</td>
        </tr>
        <tr>
            <td class="col-label">Agama</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ $data['agama'] }}</td>
        </tr>
        <tr>
            <td class="col-label">Pekerjaan</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ $data['pekerjaan'] }}</td>
        </tr>
        <tr>
            <td class="col-label">Golongan Darah</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ $data['golongan_darah'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="col-label">Kewarganegaraan</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ $data['kewarganegaraan'] }}</td>
        </tr>
        <tr>
            <td class="col-label">Alamat</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ $data['alamat'] }}</td>
        </tr>
        <tr>
            <td class="col-label">NIK</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ $data['nik'] }}</td>
        </tr>
        <tr>
            <td class="col-label">Nomor KK</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ $data['nomor_kk'] }}</td>
        </tr>
        <tr>
            <td class="col-label">Maksud / Tujuan Mengurus</td>
            <td class="col-colon">:</td>
            <td class="col-value">{{ $data['maksud_tujuan'] }}</td>
        </tr>
    </table>

    <!-- ══ KEPERLUAN ══ -->
    @php
        $keperluanList = $data['keperluan'] ?? [];
        $items = [
            ['key' => 'kk_ktp',                   'label' => 'KK / KTP',                               'col' => 'left'],
            ['key' => 'akte_kelahiran',            'label' => 'Pengantar Akte Kelahiran / Kenal Lahir',  'col' => 'left'],
            ['key' => 'surat_kematian',            'label' => 'Pengantar Surat Kematian',                'col' => 'left'],
            ['key' => 'nikah',                     'label' => 'Pengantar Nikah',                         'col' => 'left'],
            ['key' => 'pindah',                    'label' => 'Pengantar Permohonan Pindah',             'col' => 'left'],
            ['key' => 'domisili_tinggal',          'label' => 'Surat Ket. Domisili Tempat Tinggal',      'col' => 'right'],
            ['key' => 'bepergian',                 'label' => 'Surat Ket. Bepergian / Jalan',            'col' => 'right'],
            ['key' => 'domisili_usaha',            'label' => 'Surat Ket. Domisili Usaha',               'col' => 'right'],
            ['key' => 'skck',                      'label' => 'SKCK',                                    'col' => 'right'],
            ['key' => 'lain_lain',                 'label' => 'Lain-Lain' . (!empty($data['keperluan_lain']) ? ': ' . $data['keperluan_lain'] : ''), 'col' => 'right'],
        ];
        $leftItems  = array_filter($items, fn($i) => $i['col'] === 'left');
        $rightItems = array_filter($items, fn($i) => $i['col'] === 'right');
    @endphp
    <div class="keperluan-section">
        <div class="keperluan-grid">
            <div class="keperluan-col">
                @foreach($leftItems as $item)
                    <span class="keperluan-item">
                        <span class="checkbox-box {{ in_array($item['key'], $keperluanList) ? 'checkbox-checked' : '' }}">
                            {{ in_array($item['key'], $keperluanList) ? '✓' : '' }}
                        </span>
                        {{ $item['label'] }}
                    </span>
                @endforeach
            </div>
            <div class="keperluan-col">
                @foreach($rightItems as $item)
                    <span class="keperluan-item">
                        <span class="checkbox-box {{ in_array($item['key'], $keperluanList) ? 'checkbox-checked' : '' }}">
                            {{ in_array($item['key'], $keperluanList) ? '✓' : '' }}
                        </span>
                        {{ $item['label'] }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- ══ ALAMAT DITUJU ══ -->
    <div class="alamat-section">
        <table class="alamat-table">
            <tr>
                <td class="a-label">Alamat yang dituju :</td>
                <td class="a-value" style="width: 220px;">{{ $data['alamat_dituju'] ?? '' }}</td>
                <td class="a-label" style="padding-left: 12px;">No.</td>
                <td class="a-value" style="width: 45px;">{{ $data['nomor_dituju'] ?? '' }}</td>
                <td class="a-label" style="padding-left: 12px;">RT.</td>
                <td class="a-value" style="width: 45px;">{{ $data['rt_dituju'] ?? '' }}</td>
                <td style="width: 100%;"></td>
            </tr>
        </table>
        <table class="alamat-table alamat-indent">
            <tr>
                <td class="a-label">Kelurahan</td>
                <td class="a-value">{{ $data['kelurahan_dituju'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="a-label">Kecamatan</td>
                <td class="a-value">{{ $data['kecamatan_dituju'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="a-label">Kab / Kota</td>
                <td class="a-value">{{ $data['kab_kota_dituju'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="a-label">Provinsi</td>
                <td class="a-value">{{ $data['provinsi_dituju'] ?? '' }}</td>
            </tr>
        </table>
    </div>

    @php $pengikut = $data['pengikut'] ?? []; @endphp
    {{-- Bila ada pengikut: Jumlah Pengikut, tabel, dan penutup/TTD dipindah ke halaman 2 --}}
    @if(count($pengikut) > 0)
    <div class="page-two">
        <!-- ══ JUMLAH PENGIKUT ══ -->
        <table class="alamat-table" style="margin-top: 8px;">
            <tr>
                <td class="a-label">Jumlah Pengikut :</td>
                <td class="a-value" style="width: 28px; text-align: center;">{{ count($pengikut) }}</td>
                <td class="a-label" style="padding-left: 6px;">Orang</td>
                <td style="width: 100%;"></td>
            </tr>
        </table>

        <!-- ══ TABEL PENGIKUT ══ -->
        <table class="pengikut-table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-nama">NAMA</th>
                    <th class="col-hub">HUB. KELUARGA</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pengikut as $i => $p)
                    <tr>
                        <td class="col-no">{{ $i + 1 }}</td>
                        <td>{{ $p['nama'] }}</td>
                        <td>{{ $p['hub_keluarga'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- ══ PENUTUP ══ -->
    <div class="footer-section">
        <div style="line-height: 1.5;">
            Demikian Surat Pengantar ini diberikan kepada yang bersangkutan untuk dipergunakan sebagaimana mestinya.
        </div>
        <div class="footer-sign">
            <div class="sign-box">
                <div>Ketua RT. 44</div>
                <div class="sign-gap"></div>
                <div class="sign-name">AIDIL SAPUTRA KIRSAN</div>
            </div>
        </div>
    </div>

    @if(count($pengikut) > 0)
    </div>{{-- /.page-two --}}
    @endif

</div>
</body>
</html>
