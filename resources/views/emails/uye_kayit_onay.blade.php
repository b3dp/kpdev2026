@extends('emails.layouts.base')
@section('baslik', 'Üyelik Bildirimi')
@section('konu', 'Hesabınız Oluşturuldu')
@section('icerik')
<h1>Hoş Geldiniz!</h1>
<p>Merhaba {{ $adSoyad }},</p>
<p>Kestanepazarı Öğrenci Yetiştirme Derneği'ne üye olduğunuz için teşekkür ederiz. Hesabınız başarıyla oluşturuldu.</p>
<p style="text-align: center;">
  <a href="{{ $girisLink }}" class="btn">Hesabıma Giriş Yap</a>
</p>
<p>Sorularınız için bizimle iletişime geçebilirsiniz.</p>
@endsection
