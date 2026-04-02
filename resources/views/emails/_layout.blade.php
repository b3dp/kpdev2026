<!DOCTYPE html>
<html lang="tr" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>@yield('konu', 'Kestanepazarı')</title>

    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:AllowPNG/>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->

    <style>
        /* Sıfırlama */
        * { box-sizing: border-box; }
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f4f6f9; }

        /* Gmail blue link fix */
        u + #body a { color: inherit; text-decoration: none; font-size: inherit; font-weight: inherit; line-height: inherit; }

        /* Apple Mail mavi link fix */
        #MessageViewBody a { color: inherit; text-decoration: none; font-size: inherit; font-weight: inherit; line-height: inherit; }

        /* Mobil */
        @media only screen and (max-width: 620px) {
            .wrapper { width: 100% !important; max-width: 100% !important; }
            .container { width: 100% !important; max-width: 100% !important; padding: 0 !important; }
            .content-td { padding: 24px 20px !important; }
            .header-td { padding: 24px 20px !important; }
            .footer-td { padding: 16px 20px !important; }
            .logo { width: 60px !important; height: auto !important; }
            .otp-code { font-size: 28px !important; letter-spacing: 6px !important; }
            .btn { width: 100% !important; display: block !important; }
            h1 { font-size: 20px !important; }
            h2 { font-size: 18px !important; }
            p { font-size: 14px !important; }
        }
    </style>

    @hasSection('json_ld')
        @yield('json_ld')
    @endif
</head>
<body id="body" style="margin:0;padding:0;background-color:#f4f6f9;width:100%;">

    {{-- Outlook için wrapper --}}
    <!--[if mso | IE]>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f6f9;">
    <tr><td>
    <![endif]-->

    <table role="presentation" class="wrapper" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f6f9;padding:40px 0;">
        <tr>
            <td align="center" valign="top">

                {{-- Ana container --}}
                <table role="presentation" class="container" border="0" cellpadding="0" cellspacing="0" width="600" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="padding:0;">
                            <table role="presentation" border="0" cellpadding="0"
                                   cellspacing="0" width="100%">
                                <tr>
                                    {{-- Sol: Logo --}}
                                    <td width="130" valign="middle"
                                        style="background-color:#faf8f4;padding:16px 12px 16px 24px;width:130px;">
                                        <img src="{{ rtrim(config('app.url'), '/') }}/images/logo-kare.png"
                                             alt="Kestanepazarı"
                                             width="100"
                                             height="100"
                                             style="display:block;width:100px;height:100px;object-fit:contain;">
                                    </td>
                                    {{-- Sağ: Başlık --}}
                                    <td valign="middle"
                                        style="background-color:#faf8f4;padding:20px 24px 20px 12px;text-align:right;">
                                        <p style="color:#1e3a5f;font-size:28px;font-family:Arial,sans-serif;
                                                   font-weight:bold;margin:0;line-height:1.2;">
                                            @yield('baslik', 'Bildirim')
                                        </p>
                                    </td>
                                </tr>
                                {{-- Turuncu çizgi ayrı satırda --}}
                                <tr>
                                    <td colspan="2"
                                        style="background-color:#e07b39;height:4px;font-size:0;line-height:0;padding:0;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- İçerik --}}
                    <tr>
                        <td class="content-td" style="padding:36px 40px;">
                            @yield('icerik')
                        </td>
                    </tr>

                    {{-- Ayraç --}}
                    <tr>
                        <td style="padding:0 40px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="border-top:1px solid #e8ecf0;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td class="footer-td"
                            style="background-color:#f8f9fb;padding:20px 40px;border-top:1px solid #e8ecf0;">
                            <p style="color:#9aa3ae;font-size:11px;font-family:Arial,sans-serif;margin:0 0 6px;line-height:1.6;text-align:center;">
    <img src="{{ rtrim(config('app.url'), '/') }}/images/logo-gri.png"
         alt="Kestanepazarı"
         width="100"
         height="100"
         style="display:inline-block; vertical-align:middle; width:100px; height:100px; object-fit:contain; margin-bottom:10px;"><br/>
    <strong>Kestanepazarı Öğrenci Yetiştirme Derneği</strong><br>
    Uğur Mumcu Mah. 1234 Sok. No:5 Karabağlar / İZMİR
</p>
                            <p style="color:#9aa3ae;font-size:11px;font-family:Arial,sans-serif;margin:0;text-align:center;">
                                <a href="{{ config('app.url') }}"
                                   style="color:#1e3a5f;text-decoration:none;">kestanepazari.org.tr</a>
                                &nbsp;·&nbsp;
                                <a href="tel:+904449232"
                                   style="color:#9aa3ae;text-decoration:none;">444 9 232</a>
                            </p>
                        </td>
                    </tr>

                </table>
                {{-- /Ana container --}}

                {{-- Alt not --}}
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="padding:16px 0;">
                    <tr>
                        <td align="center">
                            <p style="color:#b0b8c4;font-size:11px;font-family:Arial,sans-serif;margin:0;line-height:1.5;">
                                Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayın.
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

    <!--[if mso | IE]>
    </td></tr></table>
    <![endif]-->

</body>
</html>
