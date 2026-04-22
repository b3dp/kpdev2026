<?php

use App\Http\Controllers\AramaController;
use App\Http\Controllers\BagisController;
use App\Http\Controllers\EkayitController;
use App\Http\Controllers\EtkinlikController;
use App\Http\Controllers\HaberAiController;
use App\Http\Controllers\HaberController;
use App\Http\Controllers\HaberOnayController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IletisimController;
use App\Http\Controllers\KurumsalController;
use App\Http\Controllers\MezunController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Üye route'ları
require __DIR__ . '/uye.php';

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/haberler', [HaberController::class, 'index'])->name('haberler.index');
Route::get('/haberler/{slug}', [HaberController::class, 'show'])->name('haberler.show');
Route::get('/etkinlikler', [EtkinlikController::class, 'index'])->name('etkinlikler.index');
Route::get('/etkinlikler/{slug}', [EtkinlikController::class, 'show'])->name('etkinlikler.show');
Route::get('/bagis', [BagisController::class, 'index'])->name('bagis.index');
Route::get('/bagis/sepet', [BagisController::class, 'sepet'])->name('bagis.sepet');
Route::get('/bagis/tesekkur', [BagisController::class, 'tesekkur'])->name('bagis.tesekkur');
Route::get('/bagis/makbuz-durum/{bagisNo}', [BagisController::class, 'makbuzDurum'])->name('bagis.makbuz-durum');
Route::post('/bagis/sepete-ekle', [BagisController::class, 'sepeteEkle'])->name('bagis.sepete-ekle');
Route::post('/bagis/sepetten-cikar/{satirId}', [BagisController::class, 'sepettenCikar'])->name('bagis.sepetten-cikar');
Route::post('/bagis/odeme', [BagisController::class, 'odemeYap'])->name('bagis.odeme');
Route::get('/bagis/{slug}', [BagisController::class, 'show'])->name('bagis.show');
Route::get('/kurumsal/{slug?}', [KurumsalController::class, 'show'])->name('kurumsal.show');
Route::get('/iletisim', [IletisimController::class, 'index'])->name('iletisim.index');
Route::post('/iletisim', [IletisimController::class, 'store'])->name('iletisim.store');
Route::get('/mezunlar', [MezunController::class, 'index'])->name('mezunlar.index');
Route::post('/mezunlar/basvuru', [MezunController::class, 'store'])->name('mezunlar.store');
Route::get('/mezunlar/{id}', [MezunController::class, 'show'])->name('mezunlar.show');
Route::get('/kayit', [EkayitController::class, 'index'])->name('ekayit.index');
Route::get('/kayit/form', [EkayitController::class, 'form'])->name('ekayit.form');
Route::post('/kayit/store', [EkayitController::class, 'store'])->name('ekayit.store');
Route::get('/kayit/evrak/{kayit}', [EkayitController::class, 'evrakIndir'])->name('ekayit.evrak.indir');
Route::get('/kayit/tesekkur', [EkayitController::class, 'tesekkur'])->name('ekayit.tesekkur');
Route::get('/arama', [AramaController::class, 'index'])->name('arama.index');
// Sitemap Index — ana giriş noktası
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
// Alt sitemap'ler
Route::get('/sitemap-static.xml', [SitemapController::class, 'static']);
Route::get('/sitemap-haberler.xml', [SitemapController::class, 'haberler']);
Route::get('/sitemap-etkinlikler.xml', [SitemapController::class, 'etkinlikler']);
Route::get('/sitemap-bagis.xml', [SitemapController::class, 'bagis']);
Route::get('/sitemap-ekayit.xml', [SitemapController::class, 'ekayit']);
Route::get('/sitemap-kurumsal.xml', [SitemapController::class, 'kurumsal']);
// SEO: LLM araclari icin metin tabanli index cikisi.
Route::get('/llms.txt', function () {
    return response(view('llms'), 200, ['Content-Type' => 'text/plain']);
});

Route::get('/haber-onayla/{token}', [HaberOnayController::class, 'onayla'])->name('haber.onayla');
Route::get('/haber-reddet/{token}', [HaberOnayController::class, 'reddet'])->name('haber.reddet');
Route::get('/haber-onay/{haberId}/yayinla', [HaberOnayController::class, 'yayinla'])->name('haber.onay.yayinla');

Route::middleware('auth:admin')->group(function () {
    Route::post('/yonetim/haberler/{haber}/ai-baslat', [HaberAiController::class, 'baslat'])->name('haber.ai.baslat');
    Route::get('/yonetim/haberler/{haber}/ai-durum', [HaberAiController::class, 'durum'])->name('haber.ai.durum');
});
