@extends('emails._layout')

@section('baslik', 'İletişim Formu Teşekkür')

@section('konu', 'Kestanepazarı İletişim Formu')

@section('icerik')
<p style="font-size:18px;font-weight:600;margin-bottom:16px;">Sayın {{ $ad }} {{ $soyad }},</p>
<p style="font-size:15px;margin-bottom:12px;">Mesajınız başarıyla alınmıştır. En kısa sürede size dönüş yapılacaktır.</p>
<p style="font-size:14px;margin-bottom:8px;">Gönderdiğiniz konu: <strong>{{ $konu }}</strong></p>
<p style="font-size:14px;margin-bottom:8px;">Mesajınız:</p>
<blockquote style="background:#f3f4f6;padding:12px 16px;border-left:4px solid #2563eb;font-size:13px;">{{ $mesaj }}</blockquote>
<p style="font-size:13px;color:#6b7280;margin-top:18px;">Kestanepazarı İletişim Ekibi</p>
@endsection
