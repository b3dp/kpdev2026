@extends('emails.layouts.base')
@section('konu', 'E-Kayıt Başvurunuz Onaylandı — #{{ $kayitNo }}')
@section('icerik')
<h1>E-Kayıt Başvurunuz Onaylandı</h1>
<p>Merhaba {{ $adSoyad }},</p>
<p><strong>#{{ $kayitNo }}</strong> numaralı e-kayıt başvurunuz onaylanmıştır. Başvuru evraklarını aşağıdan indirebilirsiniz.</p>
<p style="text-align: center;">
  <a href="{{ $evrakUrl }}" class="btn">Evrakı İndir (PDF)</a>
</p>
<p>Başarılar dileriz!</p>
@endsection
