@extends('emails._layout')

@section('baslik', 'Doğrulama Kodu')

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Doğrulama Kodu</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">

                    {{-- Header --}}
                    <tr>
                        <td align="center" style="background-color:#1e3a5f;padding:30px 40px;">
                            <img src="{{ asset('images/logo-kare.png') }}"
                                 alt="Kestanepazarı"
                                 width="80"
                                 style="display:block;margin:0 auto 15px;">
                            <p style="color:#ffffff;font-size:13px;margin:0;opacity:0.8;">
                                Kestanepazarı Öğrenci Yetiştirme Derneği
                            </p>
                        </td>
                    </tr>

                    {{-- İçerik --}}
                    <tr>
                        <td style="padding:40px;">
                            <h2 style="color:#1e3a5f;font-size:22px;margin:0 0 15px;">
                                Giriş Doğrulama Kodu
                            </h2>
                            <p style="color:#555555;font-size:15px;line-height:1.6;margin:0 0 25px;">
                                Merhaba <strong>{{ $adSoyad }}</strong>,
                            </p>
                            <p style="color:#555555;font-size:15px;line-height:1.6;margin:0 0 30px;">
                                Hesabınıza giriş yapmak için aşağıdaki doğrulama kodunu kullanın.
                                Bu kod <strong>{{ $gecerlilik }}</strong> geçerlidir.
                            </p>

                            {{-- OTP Kodu --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:20px 0;">
                                        <div style="background-color:#f0f4ff;border:2px dashed #1e3a5f;border-radius:8px;padding:20px 40px;display:inline-block;">
                                            <span style="font-size:36px;font-weight:bold;color:#1e3a5f;letter-spacing:8px;">
                                                {{ $kod }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#888888;font-size:13px;margin:25px 0 0;line-height:1.5;">
                                Bu kodu siz istemediyseniz bu e-postayı dikkate almayın.
                                Hesabınız güvende.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#f8f9fa;padding:20px 40px;border-top:1px solid #eeeeee;">
                            <p style="color:#aaaaaa;font-size:12px;margin:0;line-height:1.6;">
                                Kestanepazarı Öğrenci Yetiştirme Derneği<br>
                                Uğur Mumcu Mah. 1234 Sok. No:5 Karabağlar / İZMİR<br>
                                <a href="https://kestanepazari.org.tr"
                                   style="color:#1e3a5f;text-decoration:none;">
                                    kestanepazari.org.tr
                                </a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
