<?php

namespace App\Http\Controllers;

use App\Enums\KurumsalSablonu;
use App\Enums\RobotsKurali;
use App\Models\Etkinlik;
use App\Models\Haber;
use App\Models\HaberKategorisi;
use App\Models\KurumsalSayfa;
use App\Support\KurumsalStatikSayfalar;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KurumsalController extends Controller
{
    public function show(?string $slug = null)
    {
        if (filled($slug)) {
            $statikSayfa = KurumsalStatikSayfalar::slugIle($slug);
            if ($statikSayfa && ($statikSayfa['aktif'] ?? false)) {
                return view((string) $statikSayfa['view'], [
                    'statikSayfa' => $statikSayfa,
                ]);
            }
        }

        $yayindakiSayfalar = KurumsalSayfa::query()
            ->where('durum', 'yayinda')
            ->with([
                'ustSayfa',
                'altSayfalar' => fn ($query) => $query->where('durum', 'yayinda')->orderBy('sira'),
                'gorseller',
                'lokasyonlar',
                'kurum',
            ])
            ->orderBy('sira')
            ->get();

        if (! filled($slug)) {
            $standartSayfalar = $yayindakiSayfalar
                ->where('sablon', KurumsalSablonu::Standart->value)
                ->values()
                ->map(function (KurumsalSayfa $sayfa): KurumsalSayfa {
                    $sayfa->kart_gorseli = $sayfa->bannerMasaustuUrl() ?: $sayfa->gorselLgUrl();

                    return $sayfa;
                });

            return view('pages.kurumsal.liste', compact('standartSayfalar'));
        }

        if ($slug === 'kurumlar') {
            $standartMenuSayfalari = $yayindakiSayfalar
                ->where('sablon', KurumsalSablonu::Standart->value)
                ->whereNull('ust_sayfa_id')
                ->values();

            $kurumSayfalari = $yayindakiSayfalar
                ->where('sablon', KurumsalSablonu::Kurum->value)
                ->values()
                ->map(function (KurumsalSayfa $sayfa): KurumsalSayfa {
                    $sayfa->kart_gorseli = $sayfa->bannerMasaustuUrl() ?: $sayfa->gorselLgUrl();

                    return $sayfa;
                });

            return view('pages.kurumsal.kurumlar-liste', compact('kurumSayfalari', 'standartMenuSayfalari'));
        }

        if ($slug === 'atolyeler') {
            $standartMenuSayfalari = $yayindakiSayfalar
                ->where('sablon', KurumsalSablonu::Standart->value)
                ->whereNull('ust_sayfa_id')
                ->values();

            $atolyeSayfalari = $yayindakiSayfalar
                ->where('sablon', KurumsalSablonu::Atolye->value)
                ->values()
                ->map(function (KurumsalSayfa $sayfa): KurumsalSayfa {
                    $sayfa->kart_gorseli = $sayfa->bannerMasaustuUrl() ?: $sayfa->gorselLgUrl();

                    return $sayfa;
                });

            return view('pages.kurumsal.atolyeler-liste', compact('atolyeSayfalari', 'standartMenuSayfalari'));
        }

        $menuSayfalari = $yayindakiSayfalar
            ->whereNull('ust_sayfa_id')
            ->values()
            ->merge($this->varsayilanMenuSayfalari())
            ->unique('slug')
            ->values();

        $varsayilanAcilisSayfasi = $yayindakiSayfalar->firstWhere('slug', 'hakkimizda')
            ?? $menuSayfalari->firstWhere('slug', 'hakkimizda')
            ?? $yayindakiSayfalar->firstWhere('ust_sayfa_id', null)
            ?? $menuSayfalari->first()
            ?? $this->varsayilanSayfaOlustur();

        $arananSayfa = $slug ? $yayindakiSayfalar->firstWhere('slug', $slug) : null;
        $sayfa = $arananSayfa
            ?? ($slug ? $this->varsayilanSayfaOlustur($slug) : $varsayilanAcilisSayfasi);

        $ilgiliHaberler = Haber::query()
            ->with('kategori')
            ->where('durum', 'yayinda')
            ->when($sayfa->kurum_id, function ($query) use ($sayfa) {
                $query->whereHas('kurumlar', function ($kurumQuery) use ($sayfa) {
                    $kurumQuery->where('kurumlar.id', $sayfa->kurum_id)
                        ->where('haber_kurumlar.onay_durumu', 'onaylandi');
                });
            })
            ->latest('yayin_tarihi')
            ->take(3)
            ->get();

        if ($ilgiliHaberler->isEmpty()) {
            $ilgiliHaberler = Haber::query()
                ->with('kategori')
                ->where('durum', 'yayinda')
                ->latest('yayin_tarihi')
                ->take(3)
                ->get();
        }

        $yaklasanEtkinlikler = Etkinlik::query()
            ->where('durum', 'yayinda')
            ->where('baslangic_tarihi', '>=', now())
            ->orderBy('baslangic_tarihi')
            ->take(3)
            ->get();

        $sonHaberler = $ilgiliHaberler;
        $kategoriler = HaberKategorisi::query()
            ->where('aktif', 1)
            ->orderBy('sira')
            ->get();

        $ustSayfa = $sayfa->ustSayfa;
        $altSayfalar = $sayfa->altSayfalar ?? collect();
        $breadcrumbSayfalari = $this->breadcrumbSayfalari($sayfa);

        return view('pages.kurumsal.index', compact(
            'sayfa',
            'menuSayfalari',
            'ustSayfa',
            'altSayfalar',
            'breadcrumbSayfalari',
            'ilgiliHaberler',
            'sonHaberler',
            'kategoriler',
            'yaklasanEtkinlikler'
        ));
    }

    private function breadcrumbSayfalari(KurumsalSayfa $sayfa): Collection
    {
        $breadcrumbSayfalari = collect();
        $ustSayfa = $sayfa->ustSayfa;

        while ($ustSayfa) {
            $breadcrumbSayfalari->prepend($ustSayfa);
            $ustSayfa = $ustSayfa->ustSayfa;
        }

        return $breadcrumbSayfalari;
    }

    private function varsayilanMenuSayfalari(): Collection
    {
        return collect([
            ['ad' => 'Hakkımızda', 'slug' => 'hakkimizda'],
            ['ad' => 'Yönetim Kurulu', 'slug' => 'yonetim-kurulu'],
            ['ad' => 'Dernek Tüzüğü', 'slug' => 'dernek-tuzugu'],
            ['ad' => 'Faaliyet Raporları', 'slug' => 'faaliyet-raporlari'],
        ])->map(fn (array $sayfa) => $this->varsayilanSayfaOlustur($sayfa['slug'], $sayfa['ad']));
    }

    private function varsayilanSayfaOlustur(?string $slug = null, ?string $ad = null): KurumsalSayfa
    {
        $slug = $slug ?: 'hakkimizda';
        $ad = $ad ?: match ($slug) {
            'yonetim-kurulu' => 'Yönetim Kurulu',
            'dernek-tuzugu', 'tuzuk' => 'Dernek Tüzüğü',
            'faaliyet-raporlari' => 'Faaliyet Raporları',
            'gizlilik-politikasi' => 'Gizlilik Politikası',
            'cerez-politikasi' => 'Çerez Politikası',
            'kvkk' => 'KVKK Aydınlatma Metni',
            'sss' => 'Sıkça Sorulan Sorular',
            default => Str::headline(str_replace('-', ' ', $slug)),
        };

        $icerik = match ($slug) {
            'yonetim-kurulu' => "Kestanepazarı'nın yönetim anlayışı; şeffaflık, istişare ve sürdürülebilir hizmet ilkelerine dayanır. Yönetim kurulumuz eğitim desteğinin her öğrenciye adil ve düzenli biçimde ulaşması için birlikte çalışır.\n\nHer dönem burs, barınma, sosyal gelişim ve mezun ilişkileri başlıklarında düzenli değerlendirmeler yapılır. Böylece hem mevcut öğrencilerimizin ihtiyaçları hem de geleceğe dönük projeler dengeli şekilde planlanır.",
            'dernek-tuzugu', 'tuzuk' => "Dernek tüzüğümüz; kurumsal kimliğimizi, çalışma esaslarımızı ve karar alma süreçlerimizi açık şekilde tanımlar. Tüm faaliyetlerimiz ilgili mevzuat ve dernek değerlerimiz doğrultusunda yürütülür.\n\nEğitim odaklı sosyal faydayı büyütmek için hesap verebilirlik, düzenli raporlama ve gönüllü katılım ilkeleri merkezdedir.",
            'faaliyet-raporlari' => "Her faaliyet döneminde öğrencilerimize sunduğumuz burs, barınma, rehberlik ve kültürel gelişim çalışmalarını düzenli olarak raporluyoruz. Bu raporlar kurumsal hafızamızı güçlendirirken bağışçılarımıza da şeffaf bir görünüm sunar.\n\nYıl boyunca yürütülen programlar; etki alanı, katılımcı sayısı ve öncelikli ihtiyaç başlıklarıyla birlikte değerlendirilir.",
            'gizlilik-politikasi' => "Ziyaretçilerimizin ve destekçilerimizin kişisel verilerini gizlilik ilkelerine uygun şekilde koruyoruz. Toplanan bilgiler yalnızca hizmet süreçlerini geliştirmek ve yasal yükümlülükleri yerine getirmek amacıyla kullanılır.\n\nDetaylı bilgi veya veri talepleriniz için bizimle iletişime geçebilirsiniz.",
            'cerez-politikasi' => "Web sitemizde kullanıcı deneyimini iyileştirmek ve performans ölçümü yapmak amacıyla çerezler kullanılabilir. Tarayıcı ayarlarınız üzerinden çerez tercihlerinizi dilediğiniz zaman yönetebilirsiniz.\n\nZorunlu olmayan çerezler ziyaretçi tercihleri doğrultusunda değerlendirilir.",
            'kvkk' => "Kişisel verileriniz, 6698 sayılı KVKK kapsamındaki yükümlülüklere uygun olarak işlenir, saklanır ve korunur. Başvuru, bağış ve iletişim süreçlerinde alınan bilgiler yalnızca belirlenen amaçlar doğrultusunda kullanılır.\n\nHaklarınıza ilişkin taleplerinizi yazılı olarak tarafımıza iletebilirsiniz.",
            'sss' => "Sıkça sorulan sorular bölümümüzde bağış, kayıt, iletişim ve kurumsal süreçlere ilişkin temel başlıkları derliyoruz. Amaç; ziyaretçilerimizin en hızlı şekilde doğru bilgiye ulaşmasını sağlamaktır.\n\nEk sorularınız için iletişim sayfamız üzerinden bize ulaşabilirsiniz.",
            default => "Kestanepazarı, uzun yıllardır öğrencilerin eğitim yolculuğunu destekleyen köklü bir dayanışma geleneğini sürdürmektedir. Kurumsal sayfalarımız; tarihçemizi, çalışma ilkelerimizi ve topluma sunduğumuz katkıları daha yakından tanımanız için hazırlandı.\n\nEğitim desteği, güven ilişkisi ve kalıcı sosyal etki anlayışıyla hareket ediyor; her çalışmamızda öğrencilerimizin gelişimini merkeze alıyoruz.",
        };

        $sayfa = new KurumsalSayfa([
            'ad' => $ad,
            'slug' => $slug,
            'sablon' => KurumsalSablonu::Standart->value,
            'icerik' => $icerik,
            'ozet' => $ad . ' sayfasında Kestanepazarı’nın kurumsal yaklaşımını, çalışma ilkelerini ve toplumsal fayda odaklı yapısını inceleyebilirsiniz.',
            'meta_description' => $ad . ' hakkında güncel kurumsal bilgiler, çalışma yaklaşımı ve temel ilkeler Kestanepazarı web sitesinde yer alır.',
            'robots' => RobotsKurali::Index->value,
            'durum' => 'yayinda',
            'sira' => 0,
        ]);

        $sayfa->setRelation('gorseller', collect());
        $sayfa->setRelation('lokasyonlar', collect());
        $sayfa->setRelation('altSayfalar', collect());
        $sayfa->setRelation('ustSayfa', null);
        $sayfa->setRelation('kurum', null);

        return $sayfa;
    }
}
