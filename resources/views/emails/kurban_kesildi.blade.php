@extends('emails._layout')

@section('konu', 'Kurbanınız Kesildi - ' . $kurbanNo)

@section('icerik')

<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;font-weight:700;margin:0 0 16px;line-height:1.3;">
    Kurbanınız Kesildi 🤲
</h2>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 24px;line-height:1.6;">
    Merhaba <strong style="color:#1e3a5f;">{{ $adSoyad }}</strong>,<br><br>
    Kurbanınız usulüne uygun olarak kesilmiştir. Allah kabul etsin, hayırlara vesile olsun.
</p>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:#f0fdf4;border-radius:8px;border-left:4px solid #22c55e;margin-bottom:24px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="color:#166534;font-size:14px;font-family:Arial,sans-serif;margin:0;line-height:1.6;">
                <strong>Kurban No:</strong> {{ $kurbanNo }}<br>
                <strong>Kesim Tarihi:</strong> {{ $kesimTarihi }}
            </p>
        </td>
    </tr>
</table>

<p style="color:#9aa3ae;font-size:12px;font-family:Arial,sans-serif;margin:0;line-height:1.6;border-top:1px solid #f0f0f0;padding-top:20px;">
    Sorularınız için <a href="mailto:bilgi@kestanepazari.org.tr" style="color:#1e3a5f;">bilgi@kestanepazari.org.tr</a> adresine yazabilirsiniz.
</p>

@endsection
