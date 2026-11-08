<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Struk {{ $bill->receipt_number }}</title>
<style>body{font-family:ui-monospace,Consolas,monospace;max-width:380px;margin:30px auto;padding:0 12px;color:#000}hr{border:0;border-top:1px dashed #000}td{padding:3px 0;vertical-align:top}td:last-child{text-align:right}h1{font-size:1.1rem;text-align:center;margin:0}.c{text-align:center}@media print{button{display:none}}</style>
</head>
<body>
<h1>{{ setting('site_name') }}</h1>
<div class="c">{{ rt_name() }} · {{ rt_location() }}</div>
<hr>
<table width="100%">
    <tr><td>No. struk</td><td>{{ $bill->receipt_number }}</td></tr>
    <tr><td>Tanggal bayar</td><td>{{ $bill->paid_at?->format('d/m/Y H:i') }}</td></tr>
    <tr><td>Keluarga</td><td>{{ $bill->household?->head_name }}<br>{{ $bill->household?->houseLabel() }}</td></tr>
    <tr><td>Tagihan</td><td>{{ $bill->name }}<br>{{ $bill->periodLabel() }}</td></tr>
    <tr><td>Alokasi</td><td>{{ $bill->allocation }}</td></tr>
    <tr><td>Metode</td><td>{{ $bill->payment_method }}</td></tr>
    <tr><td>Diverifikasi</td><td>{{ $bill->verifier?->name ?? '-' }}</td></tr>
</table>
<hr>
<table width="100%"><tr><td><strong>TOTAL</strong></td><td><strong>{{ rupiah($bill->amount) }}</strong></td></tr></table>
<hr>
<div class="c">LUNAS. Terima kasih.</div>
<p class="c"><button onclick="print()">Cetak</button></p>
</body>
</html>
