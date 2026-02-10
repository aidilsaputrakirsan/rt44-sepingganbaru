<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi - {{ $payment->id }}</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; }
        .subtitle { font-size: 14px; color: #666; }
        .details { margin-bottom: 30px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .label { font-weight: bold; }
        .status { text-align: center; font-size: 20px; color: green; border: 2px solid green; padding: 10px; display: inline-block; margin-top: 20px; transform: rotate(-5deg); }
        .footer { margin-top: 50px; text-align: right; }
        .signature { margin-top: 50px; border-top: 1px solid #333; display: inline-block; width: 200px; text-align: center; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()">Cetak / Download PDF</button>
    </div>

    <div class="header">
        <div class="title">RUKUN TETANGGA 44</div>
        <div class="subtitle">Kelurahan Sepinggan Baru, Kecamatan Balikpapan Selatan</div>
        <h3>KWITANSI PEMBAYARAN IURAN</h3>
    </div>

    <div class="details">
        <div class="row">
            <span class="label">No. Transaksi:</span>
            <span>#{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="row">
            <span class="label">Tanggal Bayar:</span>
            <span>{{ $payment->verified_at ? $payment->verified_at->format('d F Y') : $payment->created_at->format('d F Y') }}</span>
        </div>
        <div class="row">
            <span class="label">Rumah:</span>
            <span>Blok {{ $payment->due->house->blok }} No. {{ $payment->due->house->nomor }}</span>
        </div>
        <div class="row">
            <span class="label">Pemilik:</span>
            <span>{{ $payment->due->house->owner->name ?? '-' }}</span>
        </div>
        <div class="row">
            <span class="label">Periode Tagihan:</span>
            <span>{{ \Carbon\Carbon::parse($payment->due->period)->format('F Y') }}</span>
        </div>
        <div class="row" style="font-size: 18px; margin-top: 20px;">
            <span class="label">Jumlah Dibayar:</span>
            <span>Rp {{ number_format($payment->amount_paid, 0, ',', '.') }}</span>
        </div>
    </div>

    <div style="text-align: center;">
        <div class="status">LUNAS</div>
    </div>

    <div class="footer">
        <div>Balikpapan, {{ now()->format('d F Y') }}</div>
        <div class="signature">
            Bendahara RT-44<br>
            ({{ $payment->recorder ? $payment->recorder->name : 'System' }})
        </div>
    </div>

</body>
</html>
