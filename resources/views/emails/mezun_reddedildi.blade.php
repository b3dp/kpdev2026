@extends('emails._layout')

@section('baslik', 'Mezun Kaydı Hakkında')

@section('konu', 'Mezun Kaydınız Hakkında')

@section('icerik')

<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;font-weight:700;margin:0 0 16px;line-height:1.3;">
    Mezun Kaydı Hakkında
</h2>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 24px;line-height:1.6;">
    Merhaba <strong style="color:#1e3a5f;">{{ $adSoyad }}</strong>,<br><br>
    Mezun kaydınız incelendi. Maalesef aşağıdaki sebeple onaylanamadı:
</p>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:#fef2f2;border-radius:8px;border-left:4px solid #ef4444;margin-bottom:24px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="color:#991b1b;font-size:14px;font-family:Arial,sans-serif;margin:0;line-height:1.6;">
                {{ $redNotu ?? 'Kayıt bilgileriniz doğrulanamadı.' }}
            </p>
        </td>
    </tr>
</table>

<p style="color:#4a5568;font-size:14px;font-family:Arial,sans-serif;margin:0 0 20px;line-height:1.6;">
    Bilgilerinizi güncelleyerek tekrar başvurabilirsiniz.
    Sorularınız için
    <a href="mailto:bilgi@kestanepazari.org.tr" style="color:#1e3a5f;">bilgi@kestanepazari.org.tr</a>
    adresine yazabilirsiniz.
</p>

@endsection
