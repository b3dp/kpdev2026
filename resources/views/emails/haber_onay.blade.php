@extends('emails.layouts.base')
@section('konu', 'Haber Onay Talebi — {{ $haberBaslik }}')
@section('icerik')
<h1>Haber Onay Bekliyor</h1>
<p>Aşağıdaki haber yayınlanmak üzere onayınızı bekliyor:</p>
<div class="alert-box">
  <p><strong>{{ $haberBaslik }}</strong></p>
</div>
<table style="width:100%; border-spacing: 12px 0;">
  <tr>
    <td style="width:50%; text-align:center;">
      <a href="{{ $onayUrl }}" class="btn" style="background-color:#16a34a; display:block;">✓ Yayınla</a>
    </td>
    <td style="width:50%; text-align:center;">
      <a href="{{ $redUrl }}" class="btn" style="background-color:#dc2626; display:block;">✗ Reddet</a>
    </td>
  </tr>
</table>
<p style="font-size: 13px; color: #888; margin-top: 16px;">Bu linkler 1 saat geçerlidir.</p>
@endsection
