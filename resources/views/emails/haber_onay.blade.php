@extends('emails._layout')

@section('konu', 'İnceleme Bekliyor: ' . $haberBaslik)

@section('icerik')

<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;
           font-weight:700;margin:0 0 8px;line-height:1.3;">
    {{ $haberBaslik }}
</h2>

<p style="color:#888;font-size:13px;font-family:Arial,sans-serif;margin:0 0 20px;">
    <strong>Kategori:</strong> {{ $haberKategori }}
    @if($kisiler)
        &nbsp;|&nbsp; <strong>Kişiler:</strong> {{ $kisiler }}
    @endif
    @if($kurumlar)
        &nbsp;|&nbsp; <strong>Kurumlar:</strong> {{ $kurumlar }}
    @endif
</p>

@if($gorselUrl)
<img src="{{ $gorselUrl }}"
     alt="Haber görseli"
     style="width:100%;max-height:360px;object-fit:cover;
            border-radius:8px;margin-bottom:24px;display:block;">
@endif

<div style="color:#444;font-size:15px;font-family:Arial,sans-serif;
            line-height:1.7;margin-bottom:28px;
            border-left:3px solid #e07b39;padding-left:16px;">
    {{ mb_substr($haberIcerik, 0, 800) }}{{ mb_strlen($haberIcerik) > 800 ? '...' : '' }}
</div>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:28px;">
    <tr>
        <td align="center" style="padding:0 8px 12px 0;" width="50%">
            <a href="{{ $yayinlaUrl }}"
               style="background-color:#16a34a;color:#ffffff;font-family:Arial,sans-serif;
                      font-size:15px;font-weight:700;text-decoration:none;
                      padding:14px 28px;border-radius:6px;display:block;text-align:center;">
                ✅ Yayına Al
            </a>
        </td>
        <td align="center" style="padding:0 0 12px 8px;" width="50%">
            <a href="{{ $duzenleUrl }}"
               style="background-color:#2563eb;color:#ffffff;font-family:Arial,sans-serif;
                      font-size:15px;font-weight:700;text-decoration:none;
                      padding:14px 28px;border-radius:6px;display:block;text-align:center;">
                ✏️ Düzenle
            </a>
        </td>
    </tr>
</table>

<p style="color:#9aa3ae;font-size:12px;font-family:Arial,sans-serif;
          margin:0;line-height:1.6;border-top:1px solid #f0f0f0;padding-top:20px;">
    ⚠️ "Yayına Al" linki <strong>3 saat</strong> geçerlidir.
    Süre dolduktan sonra panelden yayına alabilirsiniz.
</p>

@endsection
