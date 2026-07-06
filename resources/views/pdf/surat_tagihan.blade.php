<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Surat Tagihan - {{ $tagihan->no_invoice }}</title>
<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 11pt;
        color: #000;
        line-height: 1.5;
        margin: 0;
        padding: 30px 40px;
    }
    .header {
        text-align: center;
        border-bottom: 2px solid #000;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    .header h1 {
        font-size: 18pt;
        margin: 0 0 4px 0;
        text-transform: uppercase;
    }
    .header p {
        margin: 2px 0;
        font-size: 10pt;
        color: #333;
    }
    .title {
        text-align: center;
        font-size: 14pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin: 20px 0;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }
    .info-block {
        width: 48%;
    }
    .info-block table {
        width: 100%;
        border-collapse: collapse;
    }
    .info-block td {
        padding: 2px 4px;
        font-size: 10pt;
    }
    .info-block td.label {
        font-weight: bold;
        width: 120px;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 16px;
        font-weight: bold;
        font-size: 11pt;
        border: 2px solid #000;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    table.data {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }
    table.data th {
        background: #f0f0f0;
        font-weight: bold;
        font-size: 10pt;
        text-align: left;
        padding: 8px 10px;
        border: 1px solid #000;
    }
    table.data th.right {
        text-align: right;
    }
    table.data td {
        padding: 8px 10px;
        border: 1px solid #000;
        font-size: 10pt;
    }
    table.data td.right {
        text-align: right;
    }
    table.data td.center {
        text-align: center;
    }
    table.data tr.total-row td {
        font-weight: bold;
        border-top: 2px solid #000;
    }
    .sisa-section {
        margin: 15px 0;
        padding: 10px 15px;
        border: 2px solid #000;
        text-align: center;
    }
    .sisa-section .amount {
        font-size: 16pt;
        font-weight: bold;
    }
    .signature {
        margin-top: 40px;
        display: flex;
        justify-content: space-between;
    }
    .signature-block {
        width: 45%;
        text-align: center;
    }
    .signature-block p {
        margin: 5px 0;
        font-size: 10pt;
    }
    .signature-space {
        height: 80px;
    }
    .footer {
        margin-top: 40px;
        padding-top: 15px;
        border-top: 1px solid #999;
        text-align: center;
        font-size: 9pt;
        color: #555;
    }
</style>
</head>
<body>

<div class="header">
    <h1>CV. Mega Setia Abadi</h1>
    <p>Jl. Raya Utama No. 123, Kelurahan Makmur, Kecamatan Sejahtera</p>
    <p>Telp: (021) 1234-5678 | Email: info@megasetiaabadi.co.id</p>
</div>

<div class="title">Surat Tagihan / Invoice</div>

<div class="info-row">
    <div class="info-block">
        <table>
            <tr><td class="label">No. Invoice</td><td>: {{ $tagihan->no_invoice }}</td></tr>
            <tr><td class="label">Tanggal</td><td>: {{ $tagihan->tanggal_tagihan->format('d/m/Y') }}</td></tr>
            <tr><td class="label">Jatuh Tempo</td><td>: {{ $tagihan->tanggal_jatuh_tempo->format('d/m/Y') }}</td></tr>
        </table>
    </div>
    <div class="info-block" style="text-align: right;">
        @php
            if ($tagihan->status === 'lunas') {
                $statusLabel = 'LUNAS';
            } elseif ($tagihan->is_overdue) {
                $statusLabel = 'JATUH TEMPO';
            } else {
                $statusLabel = 'BELUM LUNAS';
            }
        @endphp
        <span class="status-badge">{{ $statusLabel }}</span>
    </div>
</div>

<h3 style="margin: 20px 0 8px 0; font-size: 11pt;">Kepada Yth,</h3>
<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <tr><td style="padding: 2px 4px; width: 100px; font-weight: bold;">Nama</td><td>: {{ $tagihan->pelanggan->nama_pelanggan }}</td></tr>
    <tr><td style="padding: 2px 4px; font-weight: bold;">Alamat</td><td>: {{ $tagihan->pelanggan->alamat ?: '-' }}</td></tr>
    <tr><td style="padding: 2px 4px; font-weight: bold;">Wilayah</td><td>: {{ $tagihan->pelanggan->wilayah ?: '-' }}</td></tr>
</table>

<h3 style="margin: 20px 0 8px 0; font-size: 11pt;">Rincian Tagihan</h3>
<table class="data">
    <thead>
        <tr>
            <th style="width: 40px; text-align: center;">No</th>
            <th>Deskripsi</th>
            <th class="right">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="center">1</td>
            <td>Tagihan {{ $tagihan->no_invoice }} — {{ $tagihan->pelanggan->nama_pelanggan }}</td>
            <td class="right">Rp {{ number_format($tagihan->total_tagihan, 2, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td colspan="2" class="right">Total Tagihan</td>
            <td class="right">Rp {{ number_format($tagihan->total_tagihan, 2, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

@php
    $totalDibayar = $tagihan->pembayaran->sum('jumlah_bayar');
    $sisa = $tagihan->total_tagihan - $totalDibayar;
@endphp

@if ($tagihan->pembayaran->isNotEmpty())
    <h3 style="margin: 20px 0 8px 0; font-size: 11pt;">Riwayat Pembayaran</h3>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">No</th>
                <th>Tanggal</th>
                <th>Metode</th>
                <th>Keterangan</th>
                <th class="right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tagihan->pembayaran as $i => $pem)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $pem->tanggal_bayar->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($pem->metode_bayar) }}</td>
                    <td>{{ $pem->keterangan ?: '-' }}</td>
                    <td class="right">Rp {{ number_format($pem->jumlah_bayar, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="right">Total Pembayaran</td>
                <td class="right">Rp {{ number_format($totalDibayar, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
@endif

<div class="sisa-section">
    <p style="margin: 0 0 4px 0; font-size: 10pt;">Sisa yang Harus Dibayar</p>
    <p class="amount">Rp {{ number_format(max(0, $sisa), 2, ',', '.') }}</p>
</div>

<div class="signature">
    <div class="signature-block">
        <p>Dibuat oleh,</p>
        <div class="signature-space"></div>
        <p style="font-weight: bold;">( Bagian Administrasi )</p>
        <p style="font-size: 9pt; color: #555;">CV. Mega Setia Abadi</p>
    </div>
    <div class="signature-block">
        <p>Mengetahui,</p>
        <div class="signature-space"></div>
        <p style="font-weight: bold;">( Pimpinan )</p>
        <p style="font-size: 9pt; color: #555;">CV. Mega Setia Abadi</p>
    </div>
</div>

<div class="footer">
    Harap melakukan pembayaran sebelum {{ $tagihan->tanggal_jatuh_tempo->format('d/m/Y') }}<br>
    Bank BCA — 1234567890 — CV. Mega Setia Abadi<br>
    *Terima kasih atas kepercayaan dan kerjasama Anda*
</div>

</body>
</html>
