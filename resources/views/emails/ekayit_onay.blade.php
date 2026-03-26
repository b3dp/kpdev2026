@extends('emails._layout')

@section('konu', 'E-Kayıt Onayı - ' . $kayitNo)

@section('icerik')

<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;font-weight:700;margin:0 0 16px;line-height:1.3;">
    E-Kayıt Talebiniz Alındı ✅
</h2>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 24px;line-height:1.6;">
    Merhaba <strong style="color:#1e3a5f;">{{ $adSoyad }}</strong>,<br><br>
    Öğrenci kayıt talebiniz başarıyla alınmıştır. Evraklarınızı aşağıdan indirebilirsiniz.
</p>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:#f8f9fb;border-radius:8px;margin-bottom:28px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="color:#1e3a5f;font-size:14px;font-family:Arial,sans-serif;margin:0;line-height:1.8;">
                <strong>Kayıt No:</strong> {{ $kayitNo }}
            </p>
        </td>
    </tr>
</table>

@if(isset($evrakUrl))
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:28px;">
    <tr>
        <td align="center">
            <a href="{{ $evrakUrl }}"
               style="background-color:#f97316;color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:700;text-decoration:none;padding:14px 36px;border-radius:6px;display:inline-block;min-width:200px;text-align:center;">
                📄 Evraklarımı İndir
            </a>
        </td>
    </tr>
</table>
@endif

<p style="color:#9aa3ae;font-size:12px;font-family:Arial,sans-serif;margin:0;line-height:1.6;border-top:1px solid #f0f0f0;padding-top:20px;">
    Sorularınız için <a href="mailto:bilgi@kestanepazari.org.tr" style="color:#1e3a5f;">bilgi@kestanepazari.org.tr</a> adresine yazabilirsiniz.
</p>

@endsection
