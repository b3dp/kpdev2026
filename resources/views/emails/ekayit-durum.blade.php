@extends('emails._layout')

@section('baslik', 'E-Kayıt Bildirimi')

@section('konu', 'E-Kayıt Durum Bilgilendirmesi')

@section('icerik')

<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;font-weight:700;margin:0 0 16px;line-height:1.3;">
    E-Kayıt Durumunuz Güncellendi
</h2>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 24px;line-height:1.6;">
    Merhaba <strong style="color:#1e3a5f;">{{ $veliAdSoyad }}</strong>,<br><br>
    Öğrenci başvurunuzla ilgili güncel durum aşağıdadır.
</p>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:#f8f9fb;border-radius:8px;margin-bottom:24px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="color:#1e3a5f;font-size:14px;font-family:Arial,sans-serif;margin:0 0 8px;line-height:1.8;">
                <strong>Öğrenci:</strong> {{ $ogrenciAdSoyad ?: '—' }}
            </p>
            <p style="color:#1e3a5f;font-size:14px;font-family:Arial,sans-serif;margin:0 0 8px;line-height:1.8;">
                <strong>Sınıf:</strong> {{ $sinif ?: '—' }}
            </p>
            <p style="color:#1e3a5f;font-size:14px;font-family:Arial,sans-serif;margin:0 0 8px;line-height:1.8;">
                <strong>Kurum:</strong> {{ $kurum ?: '—' }}
            </p>
            <p style="color:#1e3a5f;font-size:14px;font-family:Arial,sans-serif;margin:0;line-height:1.8;">
                <strong>Durum:</strong> {{ $durum }}
            </p>
        </td>
    </tr>
</table>

@if(filled($evrakUrl))
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:24px;">
    <tr>
        <td align="center">
            <a href="{{ $evrakUrl }}"
               style="background-color:#f97316;color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:700;text-decoration:none;padding:14px 36px;border-radius:6px;display:inline-block;min-width:220px;text-align:center;">
                📄 PDF Evrakını İndir
            </a>
        </td>
    </tr>
</table>
@endif

@if(filled($durumNotu))
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:#fff7ed;border:1px solid #fed7aa;border-radius:8px;margin-bottom:24px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="color:#9a3412;font-size:14px;font-family:Arial,sans-serif;margin:0 0 6px;line-height:1.6;">
                <strong>Açıklama</strong>
            </p>
            <p style="color:#7c2d12;font-size:14px;font-family:Arial,sans-serif;margin:0;line-height:1.7;white-space:pre-line;">
                {{ $durumNotu }}
            </p>
        </td>
    </tr>
</table>
@endif

<p style="color:#9aa3ae;font-size:12px;font-family:Arial,sans-serif;margin:0;line-height:1.6;border-top:1px solid #f0f0f0;padding-top:20px;">
    Bu e-posta bilgilendirme amaçlıdır. Sorularınız için
    <a href="mailto:bilgi@kestanepazari.org.tr" style="color:#1e3a5f;">bilgi@kestanepazari.org.tr</a>
    adresine yazabilirsiniz.
</p>

@endsection
