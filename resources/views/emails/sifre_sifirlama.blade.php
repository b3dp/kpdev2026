@extends('emails._layout')

@section('baslik', 'Şifre Sıfırlama')
@section('konu', 'Şifre Sıfırlama Talebi')
@section('icerik')
<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;font-weight:700;margin:0 0 16px;line-height:1.3;">Şifre Sıfırlama</h2>
<p style="font-size:15px;margin-bottom:12px;">Merhaba <strong>{{ $adSoyad }}</strong>,</p>
<p style="font-size:15px;margin-bottom:12px;">Hesabınız için şifre sıfırlama talebinde bulundunuz. Aşağıdaki butona tıklayarak yeni şifrenizi belirleyebilirsiniz:</p>
<p style="text-align: center; margin: 24px 0;">
  <a href="{{ $link }}" class="btn" style="background-color:#2563eb;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-weight:700;">Şifremi Sıfırla</a>
</p>
<div style="background:#f3f4f6;padding:12px 16px;border-left:4px solid #2563eb;font-size:13px;margin-bottom:16px;">
  Bu link <strong>{{ $gecerlilik }}</strong> geçerlidir. Süre dolduktan sonra yeni bir talep oluşturmanız gerekecektir.
</div>
<p style="font-size:13px;color:#6b7280;margin-top:18px;">Bu talebi siz yapmadıysanız, lütfen bu e-postayı dikkate almayın. Hesabınız güvende.</p>
@endsection
