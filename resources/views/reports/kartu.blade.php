<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Iuran - {{ $house->blok }}/{{ $house->nomor }} - {{ $year }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            background: #fff;
            padding: 28px 32px;
            max-width: 820px;
            margin: 0 auto;
        }

        /* Header */
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #1a1a1a; padding-bottom: 12px; }
        .header h1 { font-size: 16px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .header h2 { font-size: 13px; font-weight: normal; margin-top: 3px; }
        .header h3 { font-size: 13px; font-weight: bold; margin-top: 2px; }

        /* Info warga */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; margin: 14px 0 18px; }
        .info-row { display: flex; gap: 6px; font-size: 12px; }
        .info-label { min-width: 90px; color: #555; }
        .info-colon { color: #555; }
        .info-value { font-weight: bold; }

        /* Table */
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #555; padding: 5px 7px; }
        th { background: #e8e8e8; font-weight: bold; text-align: center; font-size: 11px; }
        td { text-align: center; vertical-align: middle; }
        td.left { text-align: left; }
        td.keterangan { text-align: left; font-size: 10px; color: #333; max-width: 120px; }

        /* Status */
        .status-paid    { color: #16a34a; font-weight: bold; }
        .status-unpaid  { color: #dc2626; }
        .status-overdue { color: #d97706; }
        .status-none    { color: #9ca3af; }

        /* TTD */
        .ttd-img { width: 64px; height: auto; display: block; margin: 0 auto; }
        .ttd-cell { min-width: 80px; height: 48px; vertical-align: middle; }

        /* Footer */
        .footer { margin-top: 20px; font-size: 10px; color: #888; text-align: right; }

        /* Totals row */
        .total-row td { font-weight: bold; background: #f5f5f5; }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 320px;
            opacity: 0.07;
            pointer-events: none;
            z-index: -1;
        }

        @media print {
            body { padding: 12px 16px; }
            .footer { display: none; }
            .watermark { position: fixed; }
        }
    </style>
</head>
<body>

    {{-- Watermark --}}
    <img src="{{ asset('logort.png') }}" class="watermark" alt="">

    {{-- Header --}}
    <div class="header">
        <h1>Kartu Iuran Bulanan</h1>
        <h2>RT-44 Perumahan Sepinggan Baru, Balikpapan</h2>
        <h3>Tahun {{ $year }}</h3>
    </div>

    {{-- Info warga --}}
    <div class="info-grid">
        <div class="info-row">
            <span class="info-label">Nama</span>
            <span class="info-colon">:</span>
            <span class="info-value">{{ $house->owner->name ?? '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Status Huni</span>
            <span class="info-colon">:</span>
            <span class="info-value">{{ ucfirst($house->status_huni) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Blok / Nomor</span>
            <span class="info-colon">:</span>
            <span class="info-value">{{ $house->blok }} / {{ $house->nomor }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Status Kepemilikan</span>
            <span class="info-colon">:</span>
            <span class="info-value">{{ ucfirst($house->resident_status) }}</span>
        </div>
    </div>

    {{-- Tabel iuran --}}
    <table>
        <thead>
            <tr>
                <th style="width:28px">No.</th>
                <th style="width:80px">Bulan</th>
                <th>Tagihan</th>
                <th style="width:80px">Tgl Bayar</th>
                <th>Wajib</th>
                <th>Sukarela</th>
                <th style="width:60px">Status</th>
                <th>Keterangan</th>
                <th class="ttd-cell">TTD Warga</th>
                <th class="ttd-cell">TTD Petugas</th>
            </tr>
        </thead>
        <tbody>
            @php $totalWajib = 0; $totalSukarela = 0; @endphp
            @foreach($rows as $i => $row)
            @php
                $totalWajib    += $row['wajib'];
                $totalSukarela += $row['sukarela'];
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td class="left">{{ $row['bulan'] }}</td>
                <td>
                    @if($row['tagihan'] > 0)
                        Rp {{ number_format($row['tagihan'], 0, ',', '.') }}
                    @else
                        <span class="status-none">-</span>
                    @endif
                </td>
                <td>
                    @if($row['payment_date'])
                        {{ \Carbon\Carbon::parse($row['payment_date'])->format('d/m/Y') }}
                    @else
                        <span class="status-none">-</span>
                    @endif
                </td>
                <td>
                    @if($row['wajib'] > 0)
                        Rp {{ number_format($row['wajib'], 0, ',', '.') }}
                    @else
                        <span class="status-none">-</span>
                    @endif
                </td>
                <td>
                    @if($row['sukarela'] > 0)
                        Rp {{ number_format($row['sukarela'], 0, ',', '.') }}
                    @else
                        <span class="status-none">-</span>
                    @endif
                </td>
                <td>
                    @if($row['status'] === 'paid')
                        <span class="status-paid">LUNAS</span>
                    @elseif($row['status'] === 'unpaid')
                        <span class="status-unpaid">BELUM</span>
                    @elseif($row['status'] === 'overdue')
                        <span class="status-overdue">LEWAT</span>
                    @else
                        <span class="status-none">-</span>
                    @endif
                </td>
                <td class="keterangan">{{ $row['notes'] ?? '' }}</td>
                <td class="ttd-cell"></td>
                <td class="ttd-cell">
                    @if($row['status'] === 'paid' && $ttdBendahara)
                        <img src="{{ $ttdBendahara }}" class="ttd-img" alt="TTD Bendahara">
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="left">Total</td>
                <td>Rp {{ number_format($totalWajib, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($totalSukarela, 0, ',', '.') }}</td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
