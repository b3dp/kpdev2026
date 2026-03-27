@extends('emails._layout')

@section('konu', 'Bağış Raporu - ' . $periyot)

@section('icerik')
<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;font-weight:700;margin:0 0 16px;line-height:1.3;">
    Bağış Raporu Hazır
</h2>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 16px;line-height:1.6;">
    {{ $periyot }} bağış raporu e-posta eki olarak gönderilmiştir.
</p>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 24px;line-height:1.6;">
    Tarih aralığı: <strong style="color:#1e3a5f;">{{ $tarihAraligi }}</strong>
</p>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 24px;line-height:1.6;">
    Drive bağlantısı: <a href="{{ $driveUrl }}" style="color:#1e3a5f;">Raporu Google Drive üzerinde aç</a>
</p>
@endsection