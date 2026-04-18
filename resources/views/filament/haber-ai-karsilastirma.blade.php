@php
    $revizyonlar = $haber->aiRevizyonlari->values();
    $revizyon = $revizyonlar->first();
    $diff = $revizyon?->diff_ozeti_json ?? [];
    $satirlar = $revizyon
        ? app(\App\Services\HaberAiDiffService::class)->satirBazliDiffHazirla(
            (string) ($revizyon->orijinal_icerik ?? ''),
            (string) ($revizyon->duzeltilmis_icerik ?? ''),
        )
        : [];
    $fark_ornekleri = $revizyon
        ? app(\App\Services\HaberAiDiffService::class)->farkOrnekleriHazirla(
            (string) ($revizyon->orijinal_icerik ?? ''),
            (string) ($revizyon->duzeltilmis_icerik ?? ''),
        )
        : [];
@endphp

<div
    x-data="{ sadeceDegisenler: false }"
    class="space-y-5"
>
    @if($revizyon)
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Revizyon Geçmişi</p>
                    <p class="mt-1 text-sm text-slate-600">En yeni revizyon üstte gösterilir. Şu an ilk kayıt ayrıntılı açılıyor.</p>
                </div>
                <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                    Toplam {{ $revizyonlar->count() }} revizyon
                </div>
            </div>

            <div class="mt-4 space-y-2 max-h-52 overflow-auto pr-1">
                @foreach($revizyonlar as $kayit)
                    <div class="rounded-xl border {{ $loop->first ? 'border-primary-200 bg-primary-50/60' : 'border-slate-200 bg-slate-50' }} px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $kayit->created_at?->format('d.m.Y H:i') }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ $kayit->islem_tipi }} • {{ $kayit->model ?: 'model yok' }}</p>
                            </div>
                            <div class="flex gap-2 text-xs">
                                <span class="rounded-full px-2 py-1 {{ $kayit->uygulandi_mi ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $kayit->uygulandi_mi ? 'uygulandı' : 'öneri' }}
                                </span>
                                @if($kayit->geri_alindi_mi)
                                    <span class="rounded-full bg-rose-100 px-2 py-1 text-rose-700">geri alındı</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Değişiklik Özeti</p>
                <p class="mt-2 text-sm text-amber-900">{{ $diff['degisen_cumle_sayisi'] ?? 0 }} cümlede değişiklik var</p>
                <p class="mt-1 text-sm text-amber-900">{{ $diff['degisen_kelime_sayisi'] ?? 0 }} kelime farkı var</p>
                <p class="mt-1 text-sm text-amber-900">{{ $diff['noktalama_duzeltme_sayisi'] ?? 0 }} noktalama düzeltmesi var</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Revizyon</p>
                <p class="mt-2 text-sm text-slate-900">{{ $revizyon->created_at?->format('d.m.Y H:i') }}</p>
                <p class="mt-1 text-sm text-slate-600">İşlem: {{ $revizyon->islem_tipi }}</p>
                <p class="mt-1 text-sm text-slate-600">Model: {{ $revizyon->model ?: '—' }}</p>
                <p class="mt-1 text-sm text-slate-600">Durum: {{ $revizyon->uygulandi_mi ? 'Uygulandı' : 'Öneri bekliyor' }}</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Görünüm</p>
                <label class="mt-2 flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" x-model="sadeceDegisenler" class="rounded border-slate-300 text-primary focus:ring-primary">
                    Sadece değişen satırları göster
                </label>
            </div>
        </div>

        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4">
            <h3 class="text-sm font-semibold text-sky-800">Bulunan Net Farklar</h3>
            <div class="mt-3 flex flex-wrap gap-2">
                @forelse($fark_ornekleri as $ornek)
                    <div class="rounded-full border border-sky-200 bg-white px-3 py-1 text-sm text-slate-800">
                        <span style="background:#fecdd3;color:#881337;border-radius:9999px;padding:2px 8px;">{{ $ornek['eski'] }}</span>
                        <span class="mx-1 text-slate-400">→</span>
                        <span style="background:#bbf7d0;color:#14532d;border-radius:9999px;padding:2px 8px;">{{ $ornek['yeni'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">Bu revizyonda görünür metin farkı bulunamadı.</p>
                @endforelse
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-2xl border border-rose-200 bg-rose-50/40 p-4">
                <h3 class="mb-3 text-sm font-semibold text-rose-700">Orijinal Metin</h3>
                <div class="space-y-2 max-h-[520px] overflow-auto pr-1">
                    @foreach($satirlar as $satir)
                        <div
                            x-show="!sadeceDegisenler || {{ $satir['degisti'] ? 'true' : 'false' }}"
                            class="rounded-lg px-3 py-2 text-sm leading-6 {{ $satir['degisti'] ? 'bg-white text-slate-800 border border-rose-200' : 'bg-white text-slate-700 border border-transparent' }}"
                            x-html="@js($satir['eski'])"
                        >
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-4">
                <h3 class="mb-3 text-sm font-semibold text-emerald-700">AI Düzeltilmiş Metin</h3>
                <div class="space-y-2 max-h-[520px] overflow-auto pr-1">
                    @foreach($satirlar as $satir)
                        <div
                            x-show="!sadeceDegisenler || {{ $satir['degisti'] ? 'true' : 'false' }}"
                            class="rounded-lg px-3 py-2 text-sm leading-6 {{ $satir['degisti'] ? 'bg-white text-slate-800 border border-emerald-200' : 'bg-white text-slate-700 border border-transparent' }}"
                            x-html="@js($satir['yeni'])"
                        >
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="mb-2 text-sm font-semibold text-slate-700">Özet Karşılaştırma</h3>
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-slate-800">{{ $revizyon->orijinal_ozet ?: '—' }}</div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-slate-800">{{ $revizyon->duzeltilmis_ozet ?: '—' }}</div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="mb-2 text-sm font-semibold text-slate-700">Meta Description Karşılaştırma</h3>
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-slate-800">{{ $revizyon->orijinal_meta_description ?: '—' }}</div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-slate-800">{{ $revizyon->duzeltilmis_meta_description ?: '—' }}</div>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
            Bu haber için henüz AI revizyonu bulunmuyor.
        </div>
    @endif
</div>