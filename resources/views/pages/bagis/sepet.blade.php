@extends('layouts.checkout')

@section('title', 'Sepetim')
@section('meta_description', 'Bağış sepetinizdeki kalemleri gözden geçirin, kaldırın ve ödeme adımına geçin.')
@section('robots', 'noindex, nofollow')

@section('content')
    <main class="mx-auto max-w-5xl px-4 pb-14 pt-[112px] sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-baskerville text-[28px] font-bold text-primary">Sepetim</h1>
                <p class="mt-1 font-jakarta text-sm text-teal-muted">Eklediğiniz bağış kalemlerini burada yönetebilir, isterseniz çıkarabilir ve ödeme adımına devam edebilirsiniz.</p>
            </div>
            <a href="{{ route('bagis.index') }}" class="inline-flex items-center justify-center rounded-[10px] border border-primary/10 bg-white px-4 py-2 font-jakarta text-sm font-semibold text-primary transition-colors hover:bg-bg-soft">Bağış eklemeye devam et</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.7fr,0.9fr]">
            <section class="rounded-2xl border border-primary/10 bg-white p-4 shadow-sm sm:p-5">
                @forelse ($sepet as $satir)
                    <div class="flex flex-col gap-3 border-b border-slate-100 py-4 first:pt-0 last:border-b-0 last:pb-0 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="font-jakarta text-sm font-bold text-primary">{{ $satir['ad'] ?? 'Bağış Kalemi' }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-teal-muted">
                                <span>{{ ($satir['adet'] ?? 1) > 1 ? ($satir['adet'].' adet / hisse') : '1 adet' }}</span>
                                <span>•</span>
                                <span>{{ ($satir['sahip_tipi'] ?? 'kendi') === 'baskasi' ? 'Başkası adına' : 'Kendi adıma' }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="font-baskerville text-lg font-bold text-primary">₺{{ number_format((float) ($satir['toplam'] ?? 0), 2, ',', '.') }}</span>
                            <form action="{{ route('bagis.sepetten-cikar', ['satirId' => $satir['satir_id'] ?? 0]) }}" method="POST">
                                @csrf
                                <button type="submit" class="rounded-lg border border-red-200 px-3 py-1.5 font-jakarta text-xs font-bold text-red-600 transition hover:bg-red-50">Sil</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-primary/15 bg-bg-soft px-5 py-8 text-center">
                        <p class="font-jakarta text-sm font-semibold text-primary">Sepetiniz şu an boş.</p>
                        <p class="mt-1 font-jakarta text-sm text-teal-muted">Bağış türlerinden birini seçip sepetinize eklediğinizde burada görünecektir.</p>
                    </div>
                @endforelse
            </section>

            <aside class="rounded-2xl border border-primary/10 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="font-jakarta text-sm font-bold uppercase tracking-[0.08em] text-teal-muted">Sepet Özeti</h2>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between text-sm text-teal-muted">
                        <span>Kalem Sayısı</span>
                        <span class="font-semibold text-primary">{{ count($sepet) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm text-teal-muted">
                        <span>Toplam</span>
                        <span class="font-baskerville text-xl font-bold text-primary">₺{{ number_format((float) $sepetToplam, 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="mt-5 space-y-2.5">
                    <a href="{{ $odemeSayfasiUrl }}" class="flex w-full items-center justify-center rounded-[10px] bg-orange-cta px-4 py-3 font-jakarta text-sm font-bold text-white transition-colors hover:bg-[#c94620] {{ count($sepet) === 0 ? 'pointer-events-none opacity-50' : '' }}">
                        Ödeme adımına dön
                    </a>
                    <a href="{{ route('bagis.index') }}" class="flex w-full items-center justify-center rounded-[10px] border border-primary/10 bg-white px-4 py-3 font-jakarta text-sm font-semibold text-primary transition-colors hover:bg-bg-soft">
                        Yeni bağış ekle
                    </a>
                </div>
            </aside>
        </div>
    </main>
@endsection
