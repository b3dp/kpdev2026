@extends('emails.layouts.base')
@section('konu', 'Kayıt Doğrulama Kodu')
@section('icerik')
<h1>Kayıt Doğrulama Kodu</h1>
<p>Merhaba {{ $adSoyad }},</p>
<p>Üyelik kaydınızı tamamlamak için aşağıdaki doğrulama kodunu kullanın:</p>
<div class="otp-box">
  <div class="kod">{{ $kod }}</div>
  <div class="sure">Bu kod {{ $gecerlilik }} geçerlidir.</div>
</div>
<p>Bu kodu siz talep etmediyseniz, lütfen bu e-postayı dikkate almayın.</p>
@endsection
