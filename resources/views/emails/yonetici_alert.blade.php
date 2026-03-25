@extends('emails.layouts.base')
@section('konu', '{{ $konu }}')
@section('icerik')
<h1>Sistem Bildirimi</h1>
<div class="alert-box">
  <p><strong>{{ $konu }}</strong></p>
</div>
<p style="white-space: pre-line;">{{ $mesaj }}</p>
<p style="font-size: 13px; color: #888888;">Bu e-posta otomatik olarak oluşturulmuştur — {{ now()->format('d.m.Y H:i') }}</p>
@endsection
