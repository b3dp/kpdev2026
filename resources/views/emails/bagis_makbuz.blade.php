@extends('emails._layout')

@section('konu', 'Bağışınız Alındı - ' . $bagisNo)

@section('icerik')

<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;font-weight:700;margin:0 0 16px;line-height:1.3;">
    Bağışınız için teşekkürler 🙏
</h2>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 24px;line-height:1.6;">
    Merhaba <strong style="color:#1e3a5f;">{{ $adSoyad }}</strong>,<br><br>
    Bağışınız başarıyla alınmıştır. Allah kabul etsin.
</p>

{{-- Bağış Özeti --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:#f8f9fb;border-radius:8px;margin-bottom:28px;">
    <tr>
        <td style="padding:20px 24px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="padding:6px 0;border-bottom:1px solid #e8ecf0;">
                        <p style="color:#9aa3ae;font-size:12px;font-family:Arial,sans-serif;margin:0;">BAĞIŞ NUMARASI</p>
                        <p style="color:#1e3a5f;font-size:15px;font-family:Arial,sans-serif;font-weight:700;margin:2px 0 0;">{{ $bagisNo }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-bottom:1px solid #e8ecf0;">
                        <p style="color:#9aa3ae;font-size:12px;font-family:Arial,sans-serif;margin:0;">TOPLAM TUTAR</p>
                        <p style="color:#1e3a5f;font-size:20px;font-family:Arial,sans-serif;font-weight:700;margin:2px 0 0;">{{ number_format($tutar, 2, ',', '.') }} ₺</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:10px 0;">
                        <p style="color:#9aa3ae;font-size:12px;font-family:Arial,sans-serif;margin:0;">TARİH</p>
                        <p style="color:#4a5568;font-size:14px;font-family:Arial,sans-serif;margin:2px 0 0;">{{ $tarih }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- Makbuz Butonu --}}
@if(isset($makbuzUrl))
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:28px;">
    <tr>
        <td align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml"
                style="height:48px;v-text-anchor:middle;width:240px;"
                arcsize="8%" stroke="f" fillcolor="#f97316">
            <w:anchorlock/>
            <center style="color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:bold;">
                Makbuzumu İndir
            </center>
            </v:roundrect>
            <![endif]-->
            <!--[if !mso]><!-->
            <a href="{{ $makbuzUrl }}"
               class="btn"
               style="background-color:#f97316;color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:700;text-decoration:none;padding:14px 36px;border-radius:6px;display:inline-block;mso-hide:all;min-width:200px;text-align:center;">
                📄 Makbuzumu İndir
            </a>
            <!--<![endif]-->
        </td>
    </tr>
</table>
@endif

<p style="color:#9aa3ae;font-size:12px;font-family:Arial,sans-serif;margin:0;line-height:1.6;border-top:1px solid #f0f0f0;padding-top:20px;">
    Bağışlarınız derneğimizin eğitim faaliyetlerine katkı sağlamaktadır.
    Sorularınız için <a href="mailto:bilgi@kestanepazari.org.tr" style="color:#1e3a5f;">bilgi@kestanepazari.org.tr</a> adresine yazabilirsiniz.
</p>

@endsection
