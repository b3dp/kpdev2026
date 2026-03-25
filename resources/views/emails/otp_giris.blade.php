@extends('emails.layouts.base')
@section('konu', 'Giriş Doğrulama Kodu')
@section('icerik')
<h1>Giriş Doğrulama Kodu</h1>
<p>Merhaba {{ $adSoyad }},</p>
<p>Hesabınıza giriş yapmak için aşağıdaki doğrulama kodunu kullanın:</p>
<div class="otp-box">
  <div class="kod">{{ $kod }}</div>
  <div class="sure">Bu kod {{ $gecerlilik }} geçerlidir.</div>
</div>
<p>Bu kodu siz talep etmediyseniz, lütfen bu e-postayı dikkate almayın.</p>
@endsection
