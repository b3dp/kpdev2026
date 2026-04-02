@extends('emails._layout')

@section('baslik', 'Doğrulama Kodu')

@section('konu', 'Kestanepazarı Doğrulama Kodu: ' . $kod)

@section('icerik')

<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;font-weight:700;margin:0 0 16px;line-height:1.3;">
    Doğrulama Kodunuz
</h2>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 8px;line-height:1.6;">
    Merhaba <strong style="color:#1e3a5f;">{{ $adSoyad }}</strong>,
</p>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 28px;line-height:1.6;">
    {{ $islemAdi ?? 'İşleminizi' }} tamamlamak için aşağıdaki doğrulama kodunu kullanın.
    Kod <strong>{{ $gecerlilik }}</strong> geçerlidir.
</p>

{{-- OTP Kutusu --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:28px;">
    <tr>
        <td align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml"
                style="height:80px;v-text-anchor:middle;width:280px;"
                arcsize="8%" stroke="t" strokecolor="#1e3a5f" strokeweight="2pt" fillcolor="#eef2ff">
            <w:anchorlock/>
            <center style="color:#1e3a5f;font-family:Arial,sans-serif;font-size:36px;font-weight:bold;letter-spacing:10px;">
                {{ $kod }}
            </center>
            </v:roundrect>
            <![endif]-->
            <!--[if !mso]><!-->
            <div style="background-color:#eef2ff;border:2px solid #1e3a5f;border-radius:8px;padding:18px 36px;display:inline-block;mso-hide:all;">
                <span class="otp-code"
                      style="font-size:36px;font-weight:700;color:#1e3a5f;font-family:'Courier New',Courier,monospace;letter-spacing:10px;display:block;">
                    {{ $kod }}
                </span>
            </div>
            <!--<![endif]-->
        </td>
    </tr>
</table>

{{-- Süre uyarısı --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:24px;">
    <tr>
        <td align="center"
            style="background-color:#fff8e1;border-left:3px solid #f59e0b;padding:10px 16px;border-radius:0 4px 4px 0;">
            <p style="color:#92400e;font-size:13px;font-family:Arial,sans-serif;margin:0;">
                ⏱ Bu kod <strong>{{ $gecerlilik }}</strong> sonra geçersiz olacak.
            </p>
        </td>
    </tr>
</table>

{{-- Güvenlik notu --}}
<p style="color:#9aa3ae;font-size:13px;font-family:Arial,sans-serif;margin:0;line-height:1.6;border-top:1px solid #f0f0f0;padding-top:20px;">
    🔒 Bu kodu <strong>kimseyle paylaşmayın.</strong>
    Kestanepazarı ekibi sizden hiçbir zaman bu kodu istemez.<br><br>
    Bu işlemi siz başlatmadıysanız bu e-postayı dikkate almayın.
    Hesabınız güvende.
</p>

@endsection
