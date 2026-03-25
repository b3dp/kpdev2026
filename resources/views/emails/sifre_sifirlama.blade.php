@extends('emails.layouts.base')
@section('konu', 'Şifre Sıfırlama Talebi')
@section('icerik')
<h1>Şifre Sıfırlama</h1>
<p>Merhaba {{ $adSoyad }},</p>
<p>Hesabınız için şifre sıfırlama talebinde bulundunuz. Aşağıdaki butona tıklayarak yeni şifrenizi belirleyebilirsiniz:</p>
<p style="text-align: center;">
  <a href="{{ $link }}" class="btn">Şifremi Sıfırla</a>
</p>
<div class="alert-box">
  <p>Bu link {{ $gecerlilik }} geçerlidir. Süre dolduktan sonra yeni bir talep oluşturmanız gerekecektir.</p>
</div>
<p>Bu talebi siz yapmadıysanız, lütfen bu e-postayı dikkate almayın. Hesabınız güvende.</p>
@endsection
