@extends('emails.layouts.base')
@section('konu', 'Mezun Kaydınız Hakkında Bilgilendirme')
@section('icerik')
<h1>Mezun Kaydı Hakkında</h1>
<p>Merhaba {{ $adSoyad }},</p>
<p>Mezun kaydınız incelenmiş; maalesef bu aşamada onaylanamamıştır.</p>
@if(!empty($redNotu))
<div class="alert-box">
  <p><strong>Gerekçe:</strong> {{ $redNotu }}</p>
</div>
@endif
<p>Daha fazla bilgi için bizimle iletişime geçebilirsiniz.</p>
@endsection
