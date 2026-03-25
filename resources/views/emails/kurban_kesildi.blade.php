@extends('emails.layouts.base')
@section('konu', 'Kurban Kesim Bildirimi — #{{ $kurbanNo }}')
@section('icerik')
<h1>Kurbanınız Kesildi</h1>
<p>Merhaba {{ $adSoyad }},</p>
<p><strong>#{{ $kurbanNo }}</strong> numaralı kurbanınız başarıyla kesilmiştir.</p>
<table style="width:100%; border-collapse:collapse; margin: 16px 0;">
  <tr>
    <td style="padding: 8px; background:#f8f9fa; font-weight:bold; width:40%;">Kurban No</td>
    <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $kurbanNo }}</td>
  </tr>
  <tr>
    <td style="padding: 8px; background:#f8f9fa; font-weight:bold;">Kesim Tarihi</td>
    <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $kesimTarihi }}</td>
  </tr>
</table>
<p>Kurban vekaletiniz için teşekkür eder, hayırlı olmasını dileriz.</p>
@endsection
