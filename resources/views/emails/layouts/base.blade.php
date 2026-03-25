<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('konu')</title>
<style>
  body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif; color: #333333; }
  .wrapper { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
  .header { background-color: #1e3a5f; padding: 24px 32px; text-align: center; }
  .header img { max-height: 50px; width: auto; }
  .header-title { color: #ffffff; font-size: 14px; margin-top: 8px; letter-spacing: 0.5px; }
  .content { padding: 32px; }
  .content h1 { font-size: 22px; color: #1e3a5f; margin: 0 0 16px 0; }
  .content p { font-size: 15px; line-height: 1.6; color: #444444; margin: 0 0 16px 0; }
  .otp-box { background-color: #f0f4ff; border: 2px dashed #1e3a5f; border-radius: 8px; padding: 20px; text-align: center; margin: 24px 0; }
  .otp-box .kod { font-size: 36px; font-weight: bold; color: #1e3a5f; letter-spacing: 8px; }
  .otp-box .sure { font-size: 13px; color: #888888; margin-top: 8px; }
  .btn { display: inline-block; background-color: #e07b39; color: #ffffff !important; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-size: 15px; font-weight: bold; margin: 16px 0; }
  .alert-box { background-color: #fff3cd; border-left: 4px solid #e07b39; padding: 16px; border-radius: 4px; margin: 16px 0; }
  .alert-box p { margin: 0; font-size: 14px; }
  .divider { border: none; border-top: 1px solid #eeeeee; margin: 24px 0; }
  .footer { background-color: #f8f9fa; padding: 24px 32px; text-align: center; border-top: 1px solid #eeeeee; }
  .footer p { font-size: 12px; color: #888888; margin: 4px 0; line-height: 1.5; }
  .footer a { color: #1e3a5f; text-decoration: none; }
  @media (max-width: 600px) {
    .content { padding: 20px; }
    .header { padding: 16px 20px; }
    .footer { padding: 16px 20px; }
  }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="header-title">Kestanepazarı Öğrenci Yetiştirme Derneği</div>
  </div>
  <div class="content">
    @yield('icerik')
  </div>
  <hr class="divider">
  <div class="footer">
    <p><strong>Kestanepazarı Öğrenci Yetiştirme Derneği</strong></p>
    <p>Bu e-posta otomatik olarak gönderilmiştir, lütfen yanıtlamayın.</p>
    @if(isset($abonelikIptalLink))
    <p><a href="{{ $abonelikIptalLink }}">E-posta bildirimlerinden çıkmak için tıklayın</a></p>
    @endif
  </div>
</div>
</body>
</html>
