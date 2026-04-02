@extends('emails._layout')

@section('json_ld')

{{-- Gmail & Yahoo: JSON-LD --}}
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "EventReservation",
    "reservationId": "{{ $kayitNo }}",
    "reservationStatus": "http://schema.org/ReservationConfirmed",
    "underName": {
        "@type": "Person",
        "name": "{{ $adSoyad }}"
    },
    "reservationFor": {
        "@type": "Event",
        "name": "Kestanepazarı E-Kayıt",
        "startDate": "{{ \Carbon\Carbon::now()->toIso8601String() }}",
        "location": {
            "@type": "Place",
            "name": "Kestanepazarı Öğrenci Yetiştirme Derneği",
            "address": {
                "@type": "PostalAddress",
                "addressLocality": "İzmir",
                "addressCountry": "TR"
            }
        },
        "organizer": {
            "@type": "Organization",
            "name": "Kestanepazarı Öğrenci Yetiştirme Derneği",
            "url": "{{ config('app.url') }}"
        }
    },
    "potentialAction": {
        "@type": "ViewAction",
        "name": "Başvurumu Görüntüle",
        "url": "{{ config('app.url') }}"
    }
}
</script>

{{-- Outlook: Actionable Messages --}}
<script type="application/adaptivecard+json">
{
    "$schema": "http://adaptivecards.io/schemas/adaptive-card.json",
    "type": "AdaptiveCard",
    "version": "1.4",
    "originator": "{{ config('app.url') }}",
    "body": [
        {
            "type": "TextBlock",
            "text": "E-Kayıt Başvurusu Alındı",
            "weight": "Bolder",
            "size": "Medium"
        },
        {
            "type": "FactSet",
            "facts": [
                { "title": "Kayıt No", "value": "{{ $kayitNo }}" },
                { "title": "Öğrenci", "value": "{{ $adSoyad }}" },
                { "title": "Sınıf", "value": "{{ $sinifAd ?? '-' }}" }
            ]
        }
    ],
    "actions": [
        {
            "type": "Action.OpenUrl",
            "title": "Başvurumu Görüntüle",
            "url": "{{ config('app.url') }}"
        }
    ]
}
</script>

{{-- Yandex Smart Widgets --}}
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "EventReservation",
    "reservationId": "{{ $kayitNo }}",
    "reservationStatus": "http://schema.org/ReservationConfirmed",
    "underName": {
        "@type": "Person",
        "name": "{{ $adSoyad }}"
    },
    "reservationFor": {
        "@type": "Event",
        "name": "Kestanepazarı E-Kayıt",
        "startDate": "{{ \Carbon\Carbon::now()->toIso8601String() }}"
    }
}
</script>

@endsection

@section('baslik', 'E-Kayıt Bildirimi')

@section('konu', 'E-Kayıt Onayı - ' . $kayitNo)

@section('icerik')

<h2 style="color:#1e3a5f;font-size:22px;font-family:Arial,sans-serif;font-weight:700;margin:0 0 16px;line-height:1.3;">
    E-Kayıt Talebiniz Alındı ✅
</h2>

<p style="color:#4a5568;font-size:15px;font-family:Arial,sans-serif;margin:0 0 24px;line-height:1.6;">
    Merhaba <strong style="color:#1e3a5f;">{{ $adSoyad }}</strong>,<br><br>
    Öğrenci kayıt talebiniz başarıyla alınmıştır. Evraklarınızı aşağıdan indirebilirsiniz.
</p>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:#f8f9fb;border-radius:8px;margin-bottom:28px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="color:#1e3a5f;font-size:14px;font-family:Arial,sans-serif;margin:0;line-height:1.8;">
                <strong>Kayıt No:</strong> {{ $kayitNo }}
            </p>
        </td>
    </tr>
</table>

@if(isset($evrakUrl))
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:28px;">
    <tr>
        <td align="center">
            <a href="{{ $evrakUrl }}"
               style="background-color:#f97316;color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:700;text-decoration:none;padding:14px 36px;border-radius:6px;display:inline-block;min-width:200px;text-align:center;">
                📄 Evraklarımı İndir
            </a>
        </td>
    </tr>
</table>
@endif

<p style="color:#9aa3ae;font-size:12px;font-family:Arial,sans-serif;margin:0;line-height:1.6;border-top:1px solid #f0f0f0;padding-top:20px;">
    Sorularınız için <a href="mailto:bilgi@kestanepazari.org.tr" style="color:#1e3a5f;">bilgi@kestanepazari.org.tr</a> adresine yazabilirsiniz.
</p>

{{-- Apple Mail / Siri: Microdata --}}
<div itemscope itemtype="http://schema.org/EventReservation" style="display:none;">
    <meta itemprop="reservationId" content="{{ $kayitNo }}"/>
    <link itemprop="reservationStatus" href="http://schema.org/ReservationConfirmed"/>
    <div itemprop="underName" itemscope itemtype="http://schema.org/Person">
        <span itemprop="name">{{ $adSoyad }}</span>
    </div>
    <div itemprop="reservationFor" itemscope itemtype="http://schema.org/Event">
        <span itemprop="name">Kestanepazarı E-Kayıt — {{ $sinifAd ?? '' }}</span>
        <meta itemprop="startDate" content="{{ \Carbon\Carbon::now()->toIso8601String() }}"/>
        <div itemprop="location" itemscope itemtype="http://schema.org/Place">
            <span itemprop="name">Kestanepazarı Öğrenci Yetiştirme Derneği</span>
        </div>
    </div>
</div>

@endsection
