@extends('emails.layouts.base')
@section('konu', 'Bağış Makbuzunuz — #{{ $bagisNo }}')
@section('icerik')
<h1>Bağışınız Alındı</h1>
<p>Merhaba {{ $adSoyad }},</p>
<p>Değerli bağışınız için teşekkür ederiz. Bağışınıza ait makbuzunuzu aşağıdan indirebilirsiniz.</p>
<table style="width:100%; border-collapse:collapse; margin: 16px 0;">
  <tr>
    <td style="padding: 8px; background:#f8f9fa; font-weight:bold; width:40%;">Bağış No</td>
    <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $bagisNo }}</td>
  </tr>
  <tr>
    <td style="padding: 8px; background:#f8f9fa; font-weight:bold;">Tutar</td>
    <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $tutar }}</td>
  </tr>
</table>
<p style="text-align: center;">
  <a href="{{ $makbuzUrl }}" class="btn">Makbuzu İndir (PDF)</a>
</p>
@endsection
