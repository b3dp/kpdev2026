<?php

namespace App\Console\Commands;

use App\Models\KurumsalSayfa;
use App\Models\KurumsalSayfaGorseli;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Throwable;

class KurumsalGorselleriWebpDonustur extends Command
{
    protected $signature = 'kurumsal:gorseller-webp-donustur {--dry-run : Sadece raporla, yazma yapma}';

    protected $description = 'Kurumsal/Atolye sayfalarindaki mevcut gorselleri webp formatina donusturur.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $manager = ImageManager::imagick();

        $sayfaGuncellenen = 0;
        $galeriGuncellenen = 0;
        $atlanan = 0;
        $hata = 0;

        KurumsalSayfa::query()
            ->select(['id', 'slug', 'gorsel_orijinal', 'gorsel_lg', 'gorsel_og', 'gorsel_sm', 'banner_orijinal', 'banner_masaustu', 'banner_mobil', 'og_gorsel'])
            ->chunkById(100, function ($sayfalar) use ($dryRun, $manager, &$sayfaGuncellenen, &$atlanan, &$hata): void {
                foreach ($sayfalar as $sayfa) {
                    try {
                        $slug = $sayfa->slug ?: 'kurumsal-sayfa-' . $sayfa->id;
                        $optDizin = "img26/opt/kurumsal/{$sayfa->id}";

                        $guncellemeler = [];

                        $anaKaynak = $sayfa->gorsel_orijinal ?: $sayfa->gorsel_lg;
                        $anaWebp = $this->webpOlustur($manager, $anaKaynak, "{$optDizin}/{$slug}-lg.webp", 85, $dryRun);
                        if ($anaWebp) {
                            $guncellemeler['gorsel_lg'] = Storage::disk('spaces')->url($anaWebp);
                            $guncellemeler['gorsel_og'] = Storage::disk('spaces')->url("{$optDizin}/{$slug}-og.webp");
                            $guncellemeler['gorsel_sm'] = Storage::disk('spaces')->url("{$optDizin}/{$slug}-sm.webp");

                            if (! $dryRun) {
                                Storage::disk('spaces')->put("{$optDizin}/{$slug}-og.webp", Storage::disk('spaces')->get($anaWebp), 'public');
                                Storage::disk('spaces')->put("{$optDizin}/{$slug}-sm.webp", Storage::disk('spaces')->get($anaWebp), 'public');
                            }
                        }

                        $bannerMasaustuKaynak = $sayfa->banner_orijinal ?: $sayfa->banner_masaustu;
                        $bannerMasaustuWebp = $this->webpOlustur($manager, $bannerMasaustuKaynak, "{$optDizin}/{$slug}-banner-masaustu.webp", 85, $dryRun);
                        if ($bannerMasaustuWebp) {
                            $guncellemeler['banner_masaustu'] = Storage::disk('spaces')->url($bannerMasaustuWebp);
                        }

                        $bannerMobilKaynak = $sayfa->banner_orijinal ?: $sayfa->banner_mobil ?: $sayfa->banner_masaustu;
                        $bannerMobilWebp = $this->webpOlustur($manager, $bannerMobilKaynak, "{$optDizin}/{$slug}-banner-mobil.webp", 85, $dryRun);
                        if ($bannerMobilWebp) {
                            $guncellemeler['banner_mobil'] = Storage::disk('spaces')->url($bannerMobilWebp);
                        }

                        $ogKaynak = $sayfa->og_gorsel ?: $sayfa->gorsel_orijinal ?: $sayfa->gorsel_lg;
                        $ogWebp = $this->webpOlustur($manager, $ogKaynak, "{$optDizin}/{$slug}-ozel-og.webp", 85, $dryRun);
                        if ($ogWebp) {
                            $guncellemeler['og_gorsel'] = Storage::disk('spaces')->url($ogWebp);
                        }

                        if ($guncellemeler === []) {
                            $atlanan++;
                            continue;
                        }

                        if (! $dryRun) {
                            $sayfa->update($guncellemeler);
                        }

                        $sayfaGuncellenen++;
                    } catch (Throwable $exception) {
                        $hata++;
                        Log::error('KurumsalGorselleriWebpDonustur sayfa hatasi', [
                            'sayfa_id' => $sayfa->id,
                            'hata' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        KurumsalSayfaGorseli::query()
            ->select(['id', 'sayfa_id', 'sira', 'orijinal_yol', 'lg_yol', 'og_yol', 'sm_yol'])
            ->chunkById(200, function ($gorseller) use ($dryRun, $manager, &$galeriGuncellenen, &$atlanan, &$hata): void {
                foreach ($gorseller as $gorsel) {
                    try {
                        $siraNo = str_pad((string) $gorsel->sira, 3, '0', STR_PAD_LEFT);
                        $slug = 'kurumsal-sayfa-' . $gorsel->sayfa_id;
                        $optDizin = "img26/opt/kurumsal/{$gorsel->sayfa_id}";

                        $kaynak = $gorsel->orijinal_yol ?: $gorsel->lg_yol;
                        if (! $kaynak) {
                            $atlanan++;
                            continue;
                        }

                        $lgYol = "{$optDizin}/{$slug}-galeri-{$siraNo}-lg.webp";
                        $ogYol = "{$optDizin}/{$slug}-galeri-{$siraNo}-og.webp";
                        $smYol = "{$optDizin}/{$slug}-galeri-{$siraNo}-sm.webp";

                        $uretildi = $this->webpOlustur($manager, $kaynak, $lgYol, 85, $dryRun);
                        if (! $uretildi) {
                            $atlanan++;
                            continue;
                        }

                        if (! $dryRun) {
                            Storage::disk('spaces')->put($ogYol, Storage::disk('spaces')->get($lgYol), 'public');
                            Storage::disk('spaces')->put($smYol, Storage::disk('spaces')->get($lgYol), 'public');

                            $gorsel->update([
                                'lg_yol' => $lgYol,
                                'og_yol' => $ogYol,
                                'sm_yol' => $smYol,
                            ]);
                        }

                        $galeriGuncellenen++;
                    } catch (Throwable $exception) {
                        $hata++;
                        Log::error('KurumsalGorselleriWebpDonustur galeri hatasi', [
                            'gorsel_id' => $gorsel->id,
                            'hata' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        $this->info('Kurumsal webp donusumu tamamlandi.');
        $this->line('Sayfa guncellenen: ' . $sayfaGuncellenen);
        $this->line('Galeri guncellenen: ' . $galeriGuncellenen);
        $this->line('Atlanan: ' . $atlanan);
        $this->line('Hata: ' . $hata);

        return self::SUCCESS;
    }

    private function webpOlustur(ImageManager $manager, ?string $kaynak, string $hedefYol, int $kalite, bool $dryRun): ?string
    {
        try {
            if (blank($kaynak)) {
                return null;
            }

            $icerik = $this->gorselIcerigiGetir((string) $kaynak);
            if (blank($icerik)) {
                return null;
            }

            if ($dryRun) {
                return $hedefYol;
            }

            $resim = $manager->read($icerik);
            Storage::disk('spaces')->put($hedefYol, (string) $resim->toWebp(quality: $kalite), 'public');

            return $hedefYol;
        } catch (Throwable $exception) {
            Log::error('KurumsalGorselleriWebpDonustur webpOlustur hatasi', [
                'kaynak' => $kaynak,
                'hedef' => $hedefYol,
                'hata' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function gorselIcerigiGetir(string $kaynak): ?string
    {
        try {
            if (filter_var($kaynak, FILTER_VALIDATE_URL)) {
                $yanit = Http::timeout(20)->get($kaynak);

                return $yanit->ok() ? $yanit->body() : null;
            }

            $duzeltilmisYol = ltrim($kaynak, '/');

            if (Storage::disk('spaces')->exists($duzeltilmisYol)) {
                return Storage::disk('spaces')->get($duzeltilmisYol);
            }

            if (Storage::disk('local')->exists($duzeltilmisYol)) {
                return Storage::disk('local')->get($duzeltilmisYol);
            }

            return null;
        } catch (Throwable $exception) {
            Log::error('KurumsalGorselleriWebpDonustur gorselIcerigiGetir hatasi', [
                'kaynak' => $kaynak,
                'hata' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
