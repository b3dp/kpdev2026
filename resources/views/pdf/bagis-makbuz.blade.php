<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Bağış Makbuzu</title>
</head>
<body style="margin:0; padding:32px; font-family: DejaVu Sans, Arial, sans-serif; color:#1f2937; background:#ffffff; font-size:12px; line-height:1.5;">
    <table style="width:100%; border-collapse:collapse; margin-bottom:24px;">
        <tr>
            <td style="width:90px; vertical-align:top;">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="Kestanepazarı" style="width:72px; height:72px; object-fit:contain;">
                @endif
            </td>
            <td style="vertical-align:top;">
                <div style="font-size:20px; font-weight:700; color:#1e3a5f; margin-bottom:6px;">Kestanepazarı Öğrenci Yetiştirme Derneği</div>
                <div style="font-size:11px; color:#4b5563;">Anafartalar Cad. No: 123 Konak / İzmir</div>
                <div style="font-size:11px; color:#4b5563;">bilgi@kestanepazari.org.tr • +90 232 000 00 00</div>
            </td>
        </tr>
    </table>

    <div style="border-top:3px solid #1e3a5f; border-bottom:1px solid #cbd5e1; padding:16px 0; margin-bottom:24px;">
        <div style="font-size:24px; font-weight:700; color:#1e3a5f; letter-spacing:0.6px;">BAĞIŞ MAKBUZU</div>
        <div style="font-size:22px; font-weight:700; margin-top:10px;">{{ $bagis->bagis_no }}</div>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-bottom:24px;">
        <tr>
            <td style="width:50%; vertical-align:top; padding-right:12px;">
                <div style="font-size:13px; font-weight:700; color:#1e3a5f; margin-bottom:8px;">Bağışçı Bilgileri</div>
                <table style="width:100%; border-collapse:collapse;">
                    <tr><td style="padding:4px 0; color:#64748b; width:90px;">Ad Soyad</td><td style="padding:4px 0;">{{ $bagisci?->ad_soyad ?? '-' }}</td></tr>
                    <tr><td style="padding:4px 0; color:#64748b;">TC</td><td style="padding:4px 0;">{{ $bagisci?->tc_kimlik ?? '-' }}</td></tr>
                    <tr><td style="padding:4px 0; color:#64748b;">Telefon</td><td style="padding:4px 0;">{{ $bagisci?->telefon ?? '-' }}</td></tr>
                    <tr><td style="padding:4px 0; color:#64748b;">E-posta</td><td style="padding:4px 0;">{{ $bagisci?->eposta ?? '-' }}</td></tr>
                </table>
            </td>
            <td style="width:50%; vertical-align:top; padding-left:12px;">
                <div style="font-size:13px; font-weight:700; color:#1e3a5f; margin-bottom:8px;">Ödeme Bilgileri</div>
                <table style="width:100%; border-collapse:collapse;">
                    <tr><td style="padding:4px 0; color:#64748b; width:110px;">Sağlayıcı</td><td style="padding:4px 0;">{{ $bagis->odeme_saglayici?->label() ?? '-' }}</td></tr>
                    <tr><td style="padding:4px 0; color:#64748b;">Referans No</td><td style="padding:4px 0;">{{ $bagis->odeme_referans ?? '-' }}</td></tr>
                    <tr><td style="padding:4px 0; color:#64748b;">Tarih</td><td style="padding:4px 0;">{{ $bagis->odeme_tarihi?->format('d.m.Y H:i') ?? '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="font-size:13px; font-weight:700; color:#1e3a5f; margin-bottom:8px;">Bağış Kalemleri</div>
    <table style="width:100%; border-collapse:collapse; margin-bottom:24px; border:1px solid #cbd5e1;">
        <thead>
            <tr style="background:#eff6ff;">
                <th style="text-align:left; padding:10px; border-bottom:1px solid #cbd5e1; color:#1e3a5f;">Bağış Türü</th>
                <th style="text-align:center; padding:10px; border-bottom:1px solid #cbd5e1; color:#1e3a5f; width:70px;">Adet</th>
                <th style="text-align:right; padding:10px; border-bottom:1px solid #cbd5e1; color:#1e3a5f; width:120px;">Birim Fiyat</th>
                <th style="text-align:right; padding:10px; border-bottom:1px solid #cbd5e1; color:#1e3a5f; width:120px;">Toplam</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bagis->kalemler as $kalem)
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #e2e8f0;">{{ $kalem->bagisTuru?->ad ?? '-' }}</td>
                    <td style="padding:10px; text-align:center; border-bottom:1px solid #e2e8f0;">{{ $kalem->adet }}</td>
                    <td style="padding:10px; text-align:right; border-bottom:1px solid #e2e8f0;">{{ number_format((float) $kalem->birim_fiyat, 2, ',', '.') }} ₺</td>
                    <td style="padding:10px; text-align:right; border-bottom:1px solid #e2e8f0;">{{ number_format((float) $kalem->toplam, 2, ',', '.') }} ₺</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-bottom:28px;">
        <tr>
            <td style="width:70%;"></td>
            <td style="width:30%; border-top:2px solid #1e3a5f; padding-top:10px; text-align:right;">
                <div style="font-size:12px; color:#64748b;">Toplam Tutar</div>
                <div style="font-size:22px; font-weight:700; color:#1e3a5f;">{{ number_format((float) $bagis->toplam_tutar, 2, ',', '.') }} ₺</div>
            </td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-top:20px;">
        <tr>
            <td style="vertical-align:top;">
                <div style="font-size:11px; color:#64748b;">Bu makbuz elektronik olarak oluşturulmuştur.</div>
            </td>
            <td style="width:120px; text-align:right; vertical-align:top;">
                <div style="width:92px; height:92px; border:1px dashed #cbd5e1; display:inline-block;"></div>
                <div style="font-size:10px; color:#94a3b8; margin-top:6px;">QR kod Faz 12</div>
            </td>
        </tr>
    </table>
</body>
</html>