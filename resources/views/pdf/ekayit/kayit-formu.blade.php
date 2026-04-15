<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Kayıt Formu</title>
    <style>
        @page {
            margin: 1.5cm;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            background: #fff;
        }
        .page {
            width: 100%;
            min-height: auto;
            padding: 0;
        }
        /* Başlık */
        .header {
            background-color: #1e3a5f;
            color: #ffffff;
            padding: 12px 16px;
            margin-bottom: 16px;
            border-radius: 4px;
        }
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .header .subtitle {
            font-size: 11px;
            margin-top: 4px;
            opacity: 0.85;
        }
        /* Sayı/sınıf bilgisi */
        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
        }
        .meta-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 10.5px;
            min-width: 180px;
        }
        .meta-box .label { color: #64748b; display: block; margin-bottom: 2px; }
        .meta-box .value { font-weight: bold; font-size: 12px; }
        /* Bölüm başlıkları */
        .section-title {
            background-color: #1e3a5f;
            color: #fff;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 6px;
            border-radius: 2px;
        }
        /* Tablo */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .info-table td {
            border: 1px solid #e2e8f0;
            padding: 5px 8px;
            font-size: 10.5px;
            vertical-align: top;
        }
        .info-table td.lbl {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            width: 27%;
            white-space: nowrap;
        }
        /* İmza alanı */
        .signature-area {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 12px;
            width: 30%;
            text-align: center;
        }
        .signature-box .sig-label {
            font-size: 10px;
            color: #64748b;
            display: block;
            margin-bottom: 30px;
        }
        .signature-box .sig-line {
            border-top: 1px solid #94a3b8;
            font-size: 9px;
            color: #94a3b8;
            padding-top: 3px;
        }
        /* Footer */
        .footer {
            margin-top: 18px;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- Başlık --}}
    <div class="header">
        <h1>E-KAYIT FORMU</h1>
        <div class="subtitle">
            {{ $sinif->donem->ad ?? '' }} / {{ $sinif->ad ?? '' }}
            @if($sinif->kurum)&nbsp;— {{ $sinif->kurum->ad }}@endif
        </div>
    </div>

    {{-- Meta --}}
    <div class="meta-row">
        <div class="meta-box">
            <span class="label">Kayıt No</span>
            <span class="value">{{ str_pad($kayit->id, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="meta-box">
            <span class="label">Kayıt Tarihi</span>
            <span class="value">{{ $kayit->created_at ? $kayit->created_at->format('d.m.Y') : '—' }}</span>
        </div>
        <div class="meta-box">
            <span class="label">Durum</span>
            <span class="value">{{ $kayit->durum instanceof \App\Enums\EkayitDurumu ? $kayit->durum->label() : $kayit->durum }}</span>
        </div>
    </div>

    {{-- Öğrenci Bilgileri --}}
    <div class="section-title">ÖĞRENCİ BİLGİLERİ</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Ad Soyad</td>
            <td>{{ $ogrenci->ad_soyad ?? '—' }}</td>
            <td class="lbl">TC Kimlik No</td>
            <td>{{ $ogrenci->tc_kimlik ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Doğum Tarihi</td>
            <td>{{ isset($ogrenci->dogum_tarihi) ? \Carbon\Carbon::parse($ogrenci->dogum_tarihi)->format('d.m.Y') : '—' }}</td>
            <td class="lbl">Doğum Yeri</td>
            <td>{{ $ogrenci->dogum_yeri ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Baba Adı</td>
            <td>{{ $ogrenci->baba_adi ?? '—' }}</td>
            <td class="lbl">Anne Adı</td>
            <td>{{ $ogrenci->anne_adi ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Cep Telefonu</td>
            <td>{{ $ogrenci->telefon ?? '—' }}</td>
            <td class="lbl">E-posta</td>
            <td>{{ $ogrenci->eposta ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">İkamet İl</td>
            <td>{{ $ogrenci->ikamet_il ?? '—' }}</td>
            <td class="lbl">İkamet İlçe</td>
            <td>{{ $ogrenci->ikamet_ilce ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Adres</td>
            <td colspan="3">{{ $ogrenci->adres ?? '—' }}</td>
        </tr>
    </table>

    {{-- Kimlik Bilgileri --}}
    <div class="section-title">KİMLİK BİLGİLERİ</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Kayıtlı İl</td>
            <td>{{ $kimlik->kayitli_il ?? '—' }}</td>
            <td class="lbl">Kayıtlı İlçe</td>
            <td>{{ $kimlik->kayitli_ilce ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Mahalle / Köy</td>
            <td>{{ $kimlik->kayitli_mahalle_koy ?? '—' }}</td>
            <td class="lbl">Kan Grubu</td>
            <td>{{ $kimlik->kan_grubu ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Cilt No</td>
            <td>{{ $kimlik->cilt_no ?? '—' }}</td>
            <td class="lbl">Aile Sıra No</td>
            <td>{{ $kimlik->aile_sira_no ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Sıra No</td>
            <td>{{ $kimlik->sira_no ?? '—' }}</td>
            <td class="lbl">Kimlik Seri No</td>
            <td>{{ $kimlik->kimlik_seri_no ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Cüzdanın Verildiği Yer</td>
            <td colspan="3">{{ $kimlik->cuzdanin_verildigi_yer ?? '—' }}</td>
        </tr>
    </table>

    {{-- Okul Bilgileri --}}
    <div class="section-title">OKUL BİLGİLERİ</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Okul Adı</td>
            <td colspan="3">{{ $okul->okul_adi ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Okul Numarası</td>
            <td>{{ $okul->okul_numarasi ?? '—' }}</td>
            <td class="lbl">Şube</td>
            <td>{{ $okul->sube ?? '—' }}</td>
        </tr>
        @if(!empty($okul->not))
        <tr>
            <td class="lbl">Not</td>
            <td colspan="3">{{ $okul->not }}</td>
        </tr>
        @endif
    </table>

    {{-- Veli Bilgileri --}}
    <div class="section-title">VELİ BİLGİLERİ</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Veli Adı Soyadı</td>
            <td>{{ $veli->ad_soyad ?? '—' }}</td>
            <td class="lbl">Telefon 1</td>
            <td>{{ $veli->telefon_1 ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Telefon 2</td>
            <td>{{ $veli->telefon_2 ?? '—' }}</td>
            <td class="lbl">E-posta</td>
            <td>{{ $veli->eposta ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">İl / İlçe</td>
            <td colspan="3">{{ collect([$veli->ikamet_il ?? null, $veli->ikamet_ilce ?? null])->filter()->implode(' / ') ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Adres</td>
            <td colspan="3">{{ $veli->adres ?? '—' }}</td>
        </tr>
    </table>

    {{-- Baba Bilgileri (doluysa) --}}
    @if($baba && ($baba->dogum_yeri || $baba->nufus_il_ilce))
    <div class="section-title">BABA BİLGİLERİ</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Doğum Yeri</td>
            <td>{{ $baba->dogum_yeri ?? '—' }}</td>
            <td class="lbl">Nüfusa Kayıtlı İl/İlçe</td>
            <td>{{ $baba->nufus_il_ilce ?? '—' }}</td>
        </tr>
    </table>
    @endif

    {{-- İmza Alanları --}}
    <div class="signature-area">
        <div class="signature-box">
            <span class="sig-label">Veli İmzası</span>
            <div class="sig-line">İmza</div>
        </div>
        <div class="signature-box">
            <span class="sig-label">Onaylayan</span>
            <div class="sig-line">İmza / Kaşe</div>
        </div>
        <div class="signature-box">
            <span class="sig-label">Tarih</span>
            <div class="sig-line">{{ now()->format('d.m.Y') }}</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Bu form {{ now()->format('d.m.Y H:i') }} tarihinde sistem tarafından oluşturulmuştur.
    </div>

</div>
</body>
</html>
