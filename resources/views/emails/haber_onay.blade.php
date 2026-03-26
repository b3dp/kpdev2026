@extends('emails._layout')

@section('konu', 'Onay Bekleyen Haber: ' . $haberBaslik)

@section('icerik')

<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;font-weight:700;margin:0 0 16px;line-height:1.3;">
    Onay Bekleyen Haber
</h2>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 16px;line-height:1.6;">
    Aşağıdaki haber yayınlanmak üzere onayınızı bekliyor:
</p>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:#f8f9fb;border-radius:8px;margin-bottom:28px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="color:#1e3a5f;font-size:16px;font-family:Arial,sans-serif;font-weight:700;margin:0;">
                {{ $haberBaslik }}
            </p>
        </td>
    </tr>
</table>

{{-- Onay/Red Butonları --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:28px;">
    <tr>
        <td align="center" style="padding:0 0 12px;">
            <!--[if !mso]><!-->
            <a href="{{ $onayUrl }}"
               style="background-color:#22c55e;color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:700;text-decoration:none;padding:14px 36px;border-radius:6px;display:inline-block;min-width:200px;text-align:center;">
                ✅ Yayınla
            </a>
            <!--<![endif]-->
        </td>
    </tr>
    <tr>
        <td align="center">
            <!--[if !mso]><!-->
            <a href="{{ $redUrl }}"
               style="background-color:#ef4444;color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:700;text-decoration:none;padding:14px 36px;border-radius:6px;display:inline-block;min-width:200px;text-align:center;">
                ❌ Reddet
            </a>
            <!--<![endif]-->
        </td>
    </tr>
</table>

<p style="color:#9aa3ae;font-size:12px;font-family:Arial,sans-serif;margin:0;line-height:1.6;border-top:1px solid #f0f0f0;padding-top:20px;">
    ⚠️ Bu linkler <strong>1 saat</strong> geçerlidir. Panelden de onaylayabilirsiniz.
</p>

@endsection
