@php
    $icerikHtml = trim((string) $sayfa->icerik);
@endphp

@if($sayfa->slug === 'hakkimizda')
    @php
        $tarihceOlaylari = [
            [
                'yil' => '1668',
                'renk' => 'is-gold',
                'baslik' => 'İlk eğitim halkası meşrutada başladı',
                'metin' => 'Kestanepazarı’nda ilk faaliyet; Evliya Çelebi’nin Seyahatnamesi’ne göre 1668 yılında İzmir Gümrük Emini Ahmet Emin Ağa tarafından yenilenen Ahmet Ağa Camii’nin meşrutasında başladı.',
            ],
            [
                'yil' => '1910',
                'renk' => 'is-alt',
                'baslik' => 'Hacı Salih Tanrıbuyruğu Hoca göreve başladı',
                'metin' => '1892 doğumlu Kurra Hafız Hacı Salih Tanrıbuyruğu Hoca, 1910 yılında medrese hocası olarak atanarak kurumsal hafızanın en güçlü taşıyıcılarından biri oldu.',
            ],
            [
                'yil' => '1911',
                'renk' => 'is-orange',
                'baslik' => 'Muhitü’t Tecvid yazıldı',
                'metin' => '1911 yılında öğrencilerin eğitimi için Osmanlıca Muhitü’t Tecvid risalesi kaleme alındı. Medrese eğitimi 1924 yılına kadar kesintisiz 15 yıl devam etti.',
            ],
            [
                'yil' => '1924',
                'renk' => '',
                'baslik' => 'Tevhid-i Tedrisat ile resmi eğitim kesildi',
                'metin' => '1924 yılında yayımlanan Tevhid-i Tedrisat Kanunu ile medreselerin kapanması üzerine yasal faaliyetler sona erdi.',
            ],
            [
                'yil' => '1925',
                'renk' => 'is-alt',
                'baslik' => 'Gayriresmi ama kesintisiz eğitim dönemi',
                'metin' => '1925-1945 yılları arasında Hacı Salih Tanrıbuyruğu Hoca öğrencilerini farklı otellerde barındırdı, yemeklerini lokantalarda yedirdi ve ikişer kişilik gruplar halinde Kur’an eğitimini sürdürdü.',
            ],
            [
                'yil' => '1947',
                'renk' => 'is-gold',
                'baslik' => 'Dernekleşme fikri resmileşti',
                'metin' => 'Diyanet İşleri 3. Başkanı Ahmet Hamdi Akseki Hoca Efendi, Kestanepazarı Camii’nde Kur’an-ı Kerim eğitimi için bir dernek kurulması gerektiğini ifade etti; bu süreç için İzmir’e Demircili Ali Eren Hoca Efendi gönderildi.',
            ],
            [
                'yil' => '1948',
                'renk' => 'is-orange',
                'baslik' => 'Kur’an Talebeleri’ni Himaye Derneği kuruldu',
                'metin' => 'Hacı Raif Cilasun, Hacı Nuri Sevil, Hacı Ahmet Tatari, Hacı Halit Seyfettin, Hacı Rıza İmren, Hacı Av. Muhtar Türkekul ve Kurra Hafız Hacı Salih Tanrıbuyruğu’nun yer aldığı Kurucular Kurulu ile dernek resmen kuruldu.',
            ],
            [
                'yil' => '1960',
                'renk' => '',
                'baslik' => 'Yeni isim, genişleyen vizyon',
                'metin' => 'Dernek 1960 yılında İmam Hatip ve İlahiyata Öğrenci Yetiştirme Derneği adını aldı ve hizmet çerçevesini büyüttü.',
            ],
            [
                'yil' => '2008',
                'renk' => 'is-gold',
                'baslik' => 'Bugünkü kimlik',
                'metin' => '2008 yılında derneğin adı Kestanepazarı Öğrenci Yetiştirme Derneği olarak değiştirildi.',
            ],
        ];

        $tarihceGorselleri = [
            ['dosya' => 'sali_tanribuyrugu.webp', 'baslik' => 'Kurra Hafız Hacı Salih Tanrıbuyruğu', 'aciklama' => 'Kurumsal hafızanın en güçlü isimlerinden biri.'],
            ['dosya' => 'genel_tarihi01.webp', 'baslik' => 'İlk dönem arşiv kareleri', 'aciklama' => 'Eğitim halkasının köklerini taşıyan görseller.'],
            ['dosya' => '1950.webp', 'baslik' => '1950’li yıllar', 'aciklama' => 'Yeniden yapılanma döneminden bir kare.'],
            ['dosya' => '1960_ihl.webp', 'baslik' => '1960 İHL dönemi', 'aciklama' => 'Kurumsal genişleme yıllarının izleri.'],
            ['dosya' => '1970_ihl.webp', 'baslik' => '1970’li yıllar', 'aciklama' => 'İmam hatip ve öğrenci yetiştirme sürecinden belge niteliğinde bir görünüm.'],
            ['dosya' => '1990li_yillar.webp', 'baslik' => '1990’lı yıllar', 'aciklama' => 'Yakın dönem hafızasından bir arşiv görüntüsü.'],
        ];

        $camiGorselleri = [
            ['dosya' => 'kestanepazarı_camii1.webp', 'baslik' => 'Kestanepazarı Camii', 'aciklama' => 'Tarihi caminin dış cephesi.'],
            ['dosya' => 'kestanepazarı_camii2.webp', 'baslik' => 'Kestanepazarı Camii çevresi', 'aciklama' => 'Kemeraltı içindeki özgün yerleşim.'],
            ['dosya' => 'kestanepazarı_camii3.webp', 'baslik' => 'Mimari detaylar', 'aciklama' => 'Kesme taş, kubbe ve minare karakteri.'],
            ['dosya' => 'kampus_karsidan.webp', 'baslik' => 'Bugünkü kampüs', 'aciklama' => 'Geçmişten bugüne taşınan hizmet alanı.'],
        ];
    @endphp

    <section id="tarihce" class="kurumsal-section-card">
        <p class="kurumsal-eyebrow">Geçmişten Bugüne</p>
        <h2 class="kurumsal-section-title">Tarihçe</h2>

        <div class="kurumsal-history-hero">
            <div class="kurumsal-history-lead kurumsal-prose">
                <p>Kestanepazarı’nda ilk faaliyet; Evliya Çelebi’nin Seyahatnamesi’ne göre, 1668 yılında İzmir Gümrük Emini Ahmet Emin Ağa tarafından, daha önce var olduğu bilinen “Kızıl İbrahim Camii” olarak anılan bir caminin yenilenmesiyle yapılan Ahmet Ağa Camii’nin meşrutasında başlamıştır.</p>
                <p>Asırlar boyunca farklı hukuki dönemlerden, yasaklardan ve dönüşümlerden geçen bu eğitim halkası; bugün de aynı heyecanı, aynı manevi odağı ve aynı öğrenci merkezli yaklaşımı taşımaktadır.</p>
            </div>

            <div class="kurumsal-history-showcase">
                <article class="kurumsal-history-featured">
                    <div class="kurumsal-history-featured-media">
                        <img src="{{ asset('images/eski/' . rawurlencode($tarihceGorselleri[0]['dosya'])) }}" alt="{{ $tarihceGorselleri[0]['baslik'] }}" loading="lazy">
                    </div>
                    <div class="kurumsal-history-featured-body">
                        <p class="kurumsal-eyebrow">Kurucu Hafıza</p>
                        <h3>{{ $tarihceGorselleri[0]['baslik'] }}</h3>
                        <p>{{ $tarihceGorselleri[0]['aciklama'] }}</p>
                    </div>
                </article>

                <div class="kurumsal-history-gallery-grid">
                    @foreach(array_slice($tarihceGorselleri, 1, 3) as $gorsel)
                        <figure class="kurumsal-history-gallery-card">
                            <div class="kurumsal-history-gallery-media">
                                <img src="{{ asset('images/eski/' . rawurlencode($gorsel['dosya'])) }}" alt="{{ $gorsel['baslik'] }}" loading="lazy">
                            </div>
                            <figcaption>
                                <strong>{{ $gorsel['baslik'] }}</strong>
                                <span>{{ $gorsel['aciklama'] }}</span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-3">
            <div class="kurumsal-stat-box">
                <strong>700</strong>
                <span>Ortaokul ve lisede ücretsiz yatılı tam pansiyon hizmet alan Kur’an talebesi</span>
            </div>
            <div class="kurumsal-stat-box">
                <strong>250</strong>
                <span>Farklı okullarda ve Kız Kur’an Kursları’nda ücretsiz yemek desteği alan öğrenci</span>
            </div>
            <div class="kurumsal-stat-box">
                <strong>260</strong>
                <span>Burs imkânı sağlanan öğrenci</span>
            </div>
        </div>

        <div class="mt-8 grid gap-8 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.9fr)]">
            <div class="space-y-5">
                @foreach($tarihceOlaylari as $olay)
                    <div class="kurumsal-timeline-item kurumsal-history-timeline-card">
                        <span class="kurumsal-timeline-dot {{ $olay['renk'] }}">{{ $olay['yil'] }}</span>
                        <div>
                            <h3>{{ $olay['baslik'] }}</h3>
                            <p>{{ $olay['metin'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="space-y-4">
                <div class="kurumsal-purpose-card is-dark">
                    <div class="kurumsal-icon-box is-dark">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12M6 12h12"/></svg>
                    </div>
                    <h3>Dirençli bir eğitim geleneği</h3>
                    <p>1925-1945 yılları arasında öğrencilerin farklı mekânlarda barındırılması, lokantalarda yemek yedirilmesi ve küçük gruplar halinde eğitimin sürdürülmesi; Kestanepazarı’nın sadece bir kurum değil, bir sebat ve adanmışlık hafızası olduğunu gösterir.</p>
                </div>

                <div class="kurumsal-history-stack">
                    @foreach(array_slice($tarihceGorselleri, 4, 2) as $gorsel)
                        <figure class="kurumsal-history-stack-card">
                            <div class="kurumsal-history-stack-media">
                                <img src="{{ asset('images/eski/' . rawurlencode($gorsel['dosya'])) }}" alt="{{ $gorsel['baslik'] }}" loading="lazy">
                            </div>
                            <figcaption>
                                <strong>{{ $gorsel['baslik'] }}</strong>
                                <span>{{ $gorsel['aciklama'] }}</span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="kurumsal-history-subsection">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="kurumsal-eyebrow">Mekân Hafızası</p>
                    <h3 class="kurumsal-section-title">İzmir’deki Kur’an Eğitiminin Temel Taşı Kestanepazarı Camii</h3>
                </div>
            </div>

            <div class="kurumsal-prose">
                <p>Konak ilçesi, Kemeraltı çarşısı içinde bulunan Kestanepazarı Camii, İzmir’in tarihi camilerindendir.</p>
                <p><strong>Yapım Tarihi:</strong> Kesin olarak bilinmemektedir. Evliya Çelebi’nin Seyahatnamesi’nde bahsettiği Ahmet Ağa Camii üzerine, yeniden 1800’lü yılların ortalarında yaptırıldığı tahmin edilmektedir.</p>
                <p><strong>Banisi:</strong> Mısırlı Hüseyin Nuri Efendi’dir. Vefatı 1291 (1874) olup mezarı İzmir Emir Sultan Türbesi Haziresi’ndedir.</p>
                <p><strong>Özellikleri:</strong> Kestanepazarı Camii’nin orijinal kitabesi günümüze ulaşamamıştır. Caminin yerinde önceden “Ahmet Ağa Camii”, ondan önce de “Kızıl İbrahim Camii” diye anılan bir cami bulunduğu kesin olarak bilinmektedir. Girişin sağındaki Latin harfli kitabenin Ahmet Ağa Camii’ne ait olduğu düşünülmektedir; Evliya Çelebi de Seyahatname’sinde bu kitabenin varlığından söz etmektedir.</p>
                <p>Kesme taştan yapılan cami iki katlıdır; alt katında dükkân ve depolar bulunur. Merdivenle çıkılan caminin önünde üç kubbeli bir son cemaat yeri yer alır ve bu bölüm yakın tarihlerde camekânla çevrilmiştir. İbadet mekânı kare planlıdır; tromplu ana kubbe, köşelerdeki küçük kubbelerle desteklenir. Niş şeklindeki mihrabın üst kısmına XIX. yüzyılda Selçuk’taki İsa Bey Camii mihrabından getirilen bir bölüm eklendiği söylenmektedir.</p>
                <p>Caminin batısındaki minaresi, kesme taş kaide üzerine yükselen yuvarlak gövdeli ve tek şerefelidir.</p>
            </div>

            <div class="kurumsal-history-cami-grid">
                @foreach($camiGorselleri as $index => $gorsel)
                    <figure class="kurumsal-history-cami-card {{ $index === 0 ? 'is-wide' : '' }}">
                        <div class="kurumsal-history-cami-media">
                            <img src="{{ asset('images/eski/' . rawurlencode($gorsel['dosya'])) }}" alt="{{ $gorsel['baslik'] }}" loading="lazy">
                        </div>
                        <figcaption>
                            <strong>{{ $gorsel['baslik'] }}</strong>
                            <span>{{ $gorsel['aciklama'] }}</span>
                        </figcaption>
                    </figure>
                @endforeach
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
@endif
