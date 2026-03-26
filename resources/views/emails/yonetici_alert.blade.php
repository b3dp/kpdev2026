@extends('emails._layout')

@section('konu', '[Sistem] ' . $konu)

@section('icerik')

<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;font-weight:700;margin:0 0 16px;line-height:1.3;">
    ⚠️ Sistem Bildirimi
</h2>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:#fef9e7;border-radius:8px;border-left:4px solid #f59e0b;margin-bottom:24px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="color:#92400e;font-size:14px;font-family:Arial,sans-serif;margin:0 0 6px;font-weight:700;">
                {{ $konu }}
            </p>
            <p style="color:#78350f;font-size:14px;font-family:Arial,sans-serif;margin:0;line-height:1.6;">
                {!! nl2br(e($mesaj)) !!}
            </p>
        </td>
    </tr>
</table>

<p style="color:#9aa3ae;font-size:11px;font-family:Arial,sans-serif;margin:0;line-height:1.6;">
    Tarih: {{ now()->format('d.m.Y H:i') }}<br>
    Bu bildirim otomatik olarak gönderilmiştir.
</p>

@endsection
