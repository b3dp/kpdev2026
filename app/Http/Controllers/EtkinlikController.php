<?php

namespace App\Http\Controllers;

use App\Enums\EtkinlikKatilimDurumu;
use App\Models\Etkinlik;
use App\Models\Haber;
use App\Models\HaberKategorisi;
use App\Services\EtkinlikKatilimService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class EtkinlikController extends Controller
{
    public function index()
    {
        $filtre = request('filtre', 'tumu');

        $etkinlikler = Etkinlik::where('durum', 'yayinda')
            ->when($filtre === 'bu-ay', fn ($q) => $q
                ->whereMonth('baslangic_tarihi', now()->month)
                ->whereYear('baslangic_tarihi', now()->year))
            ->when($filtre === 'gelecek', fn ($q) => $q
                ->where('baslangic_tarihi', '>=', now()))
            ->when($filtre === 'gecmis', fn ($q) => $q
                ->where('baslangic_tarihi', '<', now()))
            ->orderBy('baslangic_tarihi')
            ->paginate(12);

        return view('pages.etkinlikler.index', compact('etkinlikler', 'filtre'));
    }

    public function show(string $slug)
    {
        $etkinlik = Etkinlik::where('slug', $slug)
            ->where('durum', 'yayinda')
            ->with('gorseller')
            ->withCount([
                'katilimlar as katiliyorum_sayisi' => fn ($query) => $query->where('durum', EtkinlikKatilimDurumu::Katiliyorum->value),
                'katilimlar as katilmiyorum_sayisi' => fn ($query) => $query->where('durum', EtkinlikKatilimDurumu::Katilmiyorum->value),
                'katilimlar as belirsiz_sayisi' => fn ($query) => $query->where('durum', EtkinlikKatilimDurumu::Belirsiz->value),
            ])
            ->firstOrFail();

        $uye = Auth::guard('uye')->user();
        $uyeKatilimDurumu = null;

        if ($uye) {
            $uyeKatilimDurumu = $etkinlik->katilimlar()
                ->where('uye_id', $uye->id)
                ->value('durum');
        }

        $katilimSayilari = [
            EtkinlikKatilimDurumu::Katiliyorum->value => (int) $etkinlik->katiliyorum_sayisi,
            EtkinlikKatilimDurumu::Katilmiyorum->value => (int) $etkinlik->katilmiyorum_sayisi,
            EtkinlikKatilimDurumu::Belirsiz->value => (int) $etkinlik->belirsiz_sayisi,
        ];

        $sonHaberler = Haber::where('durum', 'yayinda')
            ->latest('yayin_tarihi')
            ->take(3)
            ->get();

        $kategoriler = HaberKategorisi::where('aktif', 1)
            ->orderBy('sira')
            ->get();

        $yaklasanEtkinlikler = Etkinlik::where('durum', 'yayinda')
            ->where('baslangic_tarihi', '>=', now())
            ->where('id', '!=', $etkinlik->id)
            ->orderBy('baslangic_tarihi')
            ->take(2)
            ->get();

        return view('pages.etkinlikler.detay', compact(
            'etkinlik',
            'sonHaberler',
            'kategoriler',
            'yaklasanEtkinlikler',
            'uyeKatilimDurumu',
            'katilimSayilari'
        ));
    }

    public function takvimIcs(string $slug): Response
    {
        $etkinlik = Etkinlik::where('slug', $slug)
            ->where('durum', 'yayinda')
            ->firstOrFail();

        $baslangic = $etkinlik->baslangic_tarihi?->format('Ymd\\THis');
        $bitis = $etkinlik->bitis_tarihi?->format('Ymd\\THis') ?? $etkinlik->baslangic_tarihi?->copy()->addHour()->format('Ymd\\THis');

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Kestanepazari//Etkinlik Takvimi//TR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:etkinlik-' . $etkinlik->id . '@kestanepazari.org.tr',
            'DTSTAMP:' . now()->utc()->format('Ymd\\THis\\Z'),
            'DTSTART:' . $baslangic,
            'DTEND:' . $bitis,
            'SUMMARY:' . $this->icalMetinTemizle($etkinlik->baslik),
            'DESCRIPTION:' . $this->icalMetinTemizle((string) ($etkinlik->ozet ?: strip_tags((string) $etkinlik->aciklama))),
            'LOCATION:' . $this->icalMetinTemizle((string) ($etkinlik->konum_ad ?: $etkinlik->konum_adres ?: 'Kestanepazarı')),
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        return response($ics)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $etkinlik->slug . '.ics"');
    }

    public function katilimGuncelle(Request $request, string $slug)
    {
        $istek = $request->validate([
            'durum' => ['required', 'string', 'in:' . implode(',', array_keys(EtkinlikKatilimDurumu::secenekler()))],
        ]);

        $etkinlik = Etkinlik::where('slug', $slug)
            ->where('durum', 'yayinda')
            ->firstOrFail();

        $uye = Auth::guard('uye')->user();

        if (! $uye) {
            return redirect()->route('uye.giris.form');
        }

        app(EtkinlikKatilimService::class)->katilimDurumuGuncelle(
            etkinlik: $etkinlik,
            uye: $uye,
            durum: (string) $istek['durum'],
        );

        return redirect()
            ->route('etkinlikler.show', $etkinlik->slug)
            ->with('success', 'Katılım durumunuz güncellendi.');
    }

    private function icalMetinTemizle(string $metin): string
    {
        $duzMetin = trim(preg_replace('/\s+/u', ' ', $metin) ?: '');

        return str_replace(
            ['\\', ';', ',', "\n", "\r"],
            ['\\\\', '\\;', '\\,', '\\n', ''],
            $duzMetin
        );
    }
}
