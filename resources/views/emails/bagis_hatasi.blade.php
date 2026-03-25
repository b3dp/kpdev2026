@extends('emails.layouts.base')
@section('konu', 'Ödeme İşlemi Başarısız — #{{ $bagisNo }}')
@section('icerik')
<h1>Ödeme Başarısız</h1>
<p>Merhaba {{ $adSoyad }},</p>
<p>Maalesef <strong>#{{ $bagisNo }}</strong> numaralı bağış işleminiz gerçekleştirilemedi.</p>
<div class="alert-box">
  <p><strong>Hata:</strong> {{ $hataMesaji }}</p>
</div>
<p>Lütfen kart bilgilerinizi kontrol ederek tekrar deneyiniz. Sorun devam ederse bizimle iletişime geçebilirsiniz.</p>
@endsection
