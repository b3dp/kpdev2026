{{-- mezun_onaylandi.blade.php --}}
@extends('emails._layout')

@section('konu', 'Mezun Kaydınız Onaylandı')

@section('icerik')

<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;font-weight:700;margin:0 0 16px;line-height:1.3;">
    Mezun Kaydınız Onaylandı 🎓
</h2>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 24px;line-height:1.6;">
    Merhaba <strong style="color:#1e3a5f;">{{ $adSoyad }}</strong>,<br><br>
    Mezun kaydınız incelenerek onaylanmıştır. Mezunlar ailesine hoş geldiniz!
</p>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:#f0fdf4;border-radius:8px;border-left:4px solid #22c55e;margin-bottom:24px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="color:#166534;font-size:14px;font-family:Arial,sans-serif;margin:0;">
                ✅ Profiliniz artık aktif durumda.
            </p>
        </td>
    </tr>
</table>

@endsection
