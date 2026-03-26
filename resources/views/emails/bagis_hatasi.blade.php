@extends('emails._layout')

@section('konu', 'Ödeme Hatası - ' . $bagisNo)

@section('icerik')

<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;font-weight:700;margin:0 0 16px;line-height:1.3;">
    Ödeme İşlemi Tamamlanamadı
</h2>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 24px;line-height:1.6;">
    Merhaba <strong style="color:#1e3a5f;">{{ $adSoyad }}</strong>,<br><br>
    Bağış işleminiz sırasında bir sorun oluştu. Ödemeniz alınmadı.
</p>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:#fef2f2;border-radius:8px;border-left:4px solid #ef4444;margin-bottom:24px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="color:#991b1b;font-size:14px;font-family:Arial,sans-serif;margin:0 0 4px;font-weight:700;">
                Hata Detayı:
            </p>
            <p style="color:#991b1b;font-size:14px;font-family:Arial,sans-serif;margin:0;">
                {{ $hataMesaji }}
            </p>
        </td>
    </tr>
</table>

<p style="color:#4a5568;font-size:14px;font-family:Arial,sans-serif;margin:0 0 20px;line-height:1.6;">
    Tekrar denemek için sitemizi ziyaret edebilir veya
    <a href="tel:+904449232" style="color:#1e3a5f;">444 9 232</a> numaralı hattımızı arayabilirsiniz.
</p>

<p style="color:#9aa3ae;font-size:12px;font-family:Arial,sans-serif;margin:0;line-height:1.6;border-top:1px solid #f0f0f0;padding-top:20px;">
    Bağış No: {{ $bagisNo }}
</p>

@endsection
