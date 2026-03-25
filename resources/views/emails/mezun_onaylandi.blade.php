@extends('emails.layouts.base')
@section('konu', 'Mezun Kaydınız Onaylandı')
@section('icerik')
<h1>Mezun Kaydınız Onaylandı</h1>
<p>Merhaba {{ $adSoyad }},</p>
<p>Mezun kaydınız incelenmiş ve <strong>onaylanmıştır</strong>. Artık mezun portalına erişebilirsiniz.</p>
<p>Dernek ailesine hoş geldiniz!</p>
@endsection
