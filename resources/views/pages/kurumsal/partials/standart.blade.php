@php
    $yonetimUyeleri = [
        ['ad' => 'Cüneyd Dayhan', 'gorev' => 'Dernek Başkanı', 'rozet' => 'Başkan', 'harf' => 'CY', 'renk' => 'from-[#162E4B] to-[#0d2035]'],
        ['ad' => 'Ahmet Kaya', 'gorev' => 'Başkan Yardımcısı', 'rozet' => 'Başkan Yrd.', 'harf' => 'AK', 'renk' => 'from-[#28484C] to-[#162E4B]'],
        ['ad' => 'Fatma Yıldız', 'gorev' => 'Genel Sekreter', 'rozet' => 'Sekreter', 'harf' => 'FY', 'renk' => 'from-[#1a3a28] to-[#0c2018]'],
        ['ad' => 'Mehmet Öztürk', 'gorev' => 'Sayman Üye', 'rozet' => 'Sayman', 'harf' => 'MÖ', 'renk' => 'from-[#2c3e50] to-[#1a252f]'],
    ];

    $icerikHtml = trim((string) $sayfa->icerik);
@endphp

@if($sayfa->slug === 'hakkimizda')
    <section id="tarihce" class="kurumsal-section-card">
        <p class="kurumsal-eyebrow">Geçmişten Bugüne</p>
        <h2 class="kurumsal-section-title">Tarihçe</h2>

        <div class="grid gap-8 lg:grid-cols-2 lg:items-start">
            <div class="space-y-4 text-sm leading-7 text-[#2c3e50] md:text-[15px]">
                <p>Kestanepazarı Öğrenci Yetiştirme Derneği, 1966 yılında Seferihisar’ın yerel sivil toplum öncülerinin girişimiyle kuruldu. Temel hedef; bölge gençlerinin nitelikli eğitime erişimini güçlendirmek ve fırsat eşitliğini büyütmekti.</p>
                <p>Bugün burs, barınma, kültürel gelişim ve rehberlik başlıklarında sürdürülen çalışmalar; binlerce mezunun desteğiyle daha geniş bir etki alanına ulaşıyor. Her yeni dönem, köklü mirasın çağın ihtiyaçlarına uyarlanmış bir devamı olarak şekilleniyor.</p>

                <div class="grid gap-3 pt-3 sm:grid-cols-3">
                    <div class="kurumsal-stat-box">
                        <strong>1966</strong>
                        <span>Kuruluş</span>
                    </div>
                    <div class="kurumsal-stat-box">
                        <strong>4.500+</strong>
                        <span>Mezun</span>
                    </div>
                    <div class="kurumsal-stat-box">
                        <strong>58</strong>
                        <span>Yıllık birikim</span>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="kurumsal-timeline-item">
                    <div class="kurumsal-timeline-dot">66</div>
                    <div>
                        <h3>1966 — Kuruluş</h3>
                        <p>İlk öğrenci destek yapısı oluşturuldu, dayanışma kültürü Seferihisar’da kurumsal bir zemine taşındı.</p>
                    </div>
                </div>
                <div class="kurumsal-timeline-item">
                    <div class="kurumsal-timeline-dot is-alt">80</div>
                    <div>
                        <h3>1980’ler — Büyüme</h3>
                        <p>Burs ve yurt kapasitesi genişletildi; destek modeli daha fazla öğrenciye ulaşır hale geldi.</p>
                    </div>
                </div>
                <div class="kurumsal-timeline-item">
                    <div class="kurumsal-timeline-dot is-gold">00</div>
                    <div>
                        <h3>2000’ler — Dijital dönüşüm</h3>
                        <p>Mezun ilişkileri ve bağış altyapısı dijital araçlarla desteklenmeye başladı.</p>
                    </div>
                </div>
                <div class="kurumsal-timeline-item">
                    <div class="kurumsal-timeline-dot is-orange">24</div>
                    <div>
                        <h3>2024 ve sonrası</h3>
                        <p>Yeni nesil web platformu ve içerik yapısı ile kurumsal iletişim daha erişilebilir hale geldi.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="amac" class="kurumsal-section-card">
        <p class="kurumsal-eyebrow">Neden Varız</p>
        <h2 class="kurumsal-section-title">Amaç</h2>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="kurumsal-purpose-card">
                <div class="kurumsal-icon-box">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </div>
                <h3>Temel Amaç</h3>
                <p>İhtiyaç sahibi öğrencilerin eğitim hayatını burs, barınma ve sosyal gelişim olanaklarıyla desteklemek; topluma değer katan bireylerin yetişmesine katkı sunmak.</p>
            </div>

            <div class="kurumsal-purpose-card is-dark">
                <div class="kurumsal-icon-box is-dark">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
                <h3>Uzun Vadeli Hedef</h3>
                <p>Şeffaf, sürdürülebilir ve örnek bir eğitim destek modeliyle nesiller boyu kalıcı sosyal fayda üretmek.</p>
            </div>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="kurumsal-mini-card"><strong>Şeffaflık</strong><span>Bağış ve faaliyet süreçlerini düzenli raporlarla görünür kılmak.</span></div>
            <div class="kurumsal-mini-card"><strong>Dayanışma</strong><span>Toplumsal sorumluluk ve ortak iyilik bilinciyle hareket etmek.</span></div>
            <div class="kurumsal-mini-card"><strong>Eğitime Adanmışlık</strong><span>Her öğrencinin potansiyeline ulaşmasını desteklemek.</span></div>
            <div class="kurumsal-mini-card"><strong>Hesap Verebilirlik</strong><span>Kurumsal güveni sürdüren ölçülebilir bir yapı kurmak.</span></div>
        </div>
    </section>

    <section id="hakkimizda" class="kurumsal-section-card">
        <p class="kurumsal-eyebrow">Biz Kimiz</p>
        <h2 class="kurumsal-section-title">Hakkımızda</h2>

        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-start">
            <div class="kurumsal-prose">
                @if($icerikHtml)
                    {!! $icerikHtml !!}
                @else
                    <p>Kestanepazarı Öğrenci Yetiştirme Derneği; burs programları, öğrenci yurtları ve sosyal gelişim projeleri aracılığıyla Seferihisar’ın geleceğine yatırım yapan köklü bir sivil toplum kuruluşudur.</p>
                    <p>Yüzlerce aktif üye ve bağışçı desteğiyle yürütülen faaliyetler; öğrencilerin akademik başarılarının yanında manevi, kültürel ve sosyal gelişimlerini de desteklemeyi amaçlar.</p>
                    <p>Kurumsal yaklaşımımız; şeffaflık, istişare ve kalıcı etki ilkeleri üzerine kuruludur. Her çalışma, gençlerin hayata daha donanımlı hazırlanmasına katkı sunmak için planlanır.</p>
                @endif
            </div>

            <div class="space-y-3">
                <div class="kurumsal-info-box">
                    <strong>1.250</strong>
                    <span>Aktif desteklenen öğrenci</span>
                </div>
                <div class="kurumsal-info-box">
                    <strong>9.000</strong>
                    <span>Gönüllü bağışçı ağı</span>
                </div>
                <div class="kurumsal-info-box is-dark">
                    <strong>4.500+</strong>
                    <span>Hayata hazırlanmış mezun</span>
                </div>
            </div>
        </div>

        @if($sayfa->video_embed_url)
            <div class="mt-6 overflow-hidden rounded-[20px] border border-[#162e4b]/10 bg-white shadow-sm">
                <iframe
                    src="{{ $sayfa->video_embed_url }}"
                    title="{{ $sayfa->ad }} videosu"
                    class="aspect-video w-full"
                    loading="lazy"
                    allowfullscreen
                ></iframe>
            </div>
        @endif
    </section>

    <section id="yonetim" class="kurumsal-section-card">
        <p class="kurumsal-eyebrow">Ekibimiz</p>
        <h2 class="kurumsal-section-title">Yönetim Kurulu</h2>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($yonetimUyeleri as $uye)
                <article class="yonetim-kart">
                    <div class="yonetim-foto bg-gradient-to-br {{ $uye['renk'] }}">
                        <div class="yonetim-avatar">{{ $uye['harf'] }}</div>
                        <span class="yonetim-rozet">{{ $uye['rozet'] }}</span>
                    </div>
                    <div class="p-4">
                        <h3 class="text-[15px] font-bold text-[#162e4b]">{{ $uye['ad'] }}</h3>
                        <p class="mt-1 text-sm text-[#62868d]">{{ $uye['gorev'] }}</p>
                        <p class="mt-3 text-[13px] leading-6 text-[#62868d]">Eğitim destek çalışmalarının planlanması ve sürdürülebilirliği için görev alır.</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@else
    <section class="kurumsal-section-card">
        <p class="kurumsal-eyebrow">Kurumsal içerik</p>
        <h2 class="kurumsal-section-title">{{ $sayfa->ad }}</h2>

        <div class="kurumsal-prose">
            @if($icerikHtml)
                {!! $icerikHtml !!}
            @else
                <p>{{ $sayfa->ozet ?: 'Bu sayfaya ait kurumsal içerik yakında güncellenecektir.' }}</p>
            @endif
        </div>

        @if($sayfa->video_embed_url)
            <div class="mt-6 overflow-hidden rounded-[20px] border border-[#162e4b]/10 bg-white shadow-sm">
                <iframe
                    src="{{ $sayfa->video_embed_url }}"
                    title="{{ $sayfa->ad }} videosu"
                    class="aspect-video w-full"
                    loading="lazy"
                    allowfullscreen
                ></iframe>
            </div>
        @endif
    </section>

    @if($sayfa->slug === 'yonetim-kurulu')
        <section id="yonetim" class="kurumsal-section-card">
            <p class="kurumsal-eyebrow">Kurul üyeleri</p>
            <h2 class="kurumsal-section-title">Yönetim Ekibi</h2>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($yonetimUyeleri as $uye)
                    <article class="yonetim-kart">
                        <div class="yonetim-foto bg-gradient-to-br {{ $uye['renk'] }}">
                            <div class="yonetim-avatar">{{ $uye['harf'] }}</div>
                            <span class="yonetim-rozet">{{ $uye['rozet'] }}</span>
                        </div>
                        <div class="p-4">
                            <h3 class="text-[15px] font-bold text-[#162e4b]">{{ $uye['ad'] }}</h3>
                            <p class="mt-1 text-sm text-[#62868d]">{{ $uye['gorev'] }}</p>
                            <p class="mt-3 text-[13px] leading-6 text-[#62868d]">Gönüllülük ve sürdürülebilir hizmet ilkeleriyle çalışır.</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endif
