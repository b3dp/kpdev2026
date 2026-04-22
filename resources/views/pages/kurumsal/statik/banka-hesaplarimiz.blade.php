@extends('layouts.app')

@section('title', 'Banka Hesaplarımız')
@section('meta_description', 'Kestanepazarı Öğrenci Yetiştirme Derneği banka hesap bilgileri ve hızlı kopyalama alanları.')
@section('canonical', route('kurumsal.show', ['slug' => 'banka-hesaplarimiz']))

@section('content')
<section class="mx-auto max-w-7xl px-4 py-10 lg:px-6 lg:py-12" x-data="bankaKopyalama()">
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-[#62868d]">Kurumsal</p>
        <h1 class="mt-2 font-baskerville text-3xl font-bold text-primary md:text-5xl">Banka Hesaplarımız</h1>
        <p class="mt-4 max-w-3xl font-jakarta text-[15px] leading-7 text-[#5f7480]">
            Bağışlarınızı aşağıdaki hesaplara güvenle iletebilirsiniz. IBAN ve hesap numarası alanlarında yer alan kopyala butonlarını kullanarak bilgileri tek tıkla alabilirsiniz.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <img src="{{ asset('images/albaraka_logo.svg') }}" alt="Albaraka Türk" class="h-10 w-auto object-contain">
                <span class="rounded-full bg-[#eff6ff] px-3 py-1 text-xs font-semibold text-[#1e40af]">İzmir Şubesi</span>
            </div>
            <h2 class="font-jakarta text-lg font-bold text-primary">ALBARAKA TÜRK İZMİR ŞUBESİ</h2>
            <p class="mt-1 text-sm text-[#5f7480]">Kestanepazarı Öğrenci Yetiştirme Derneği</p>

            <div class="mt-4 space-y-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#62868d]">IBAN</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <p class="font-jakarta text-sm font-semibold text-primary">TR57 0020 3000 0032 7370 0000 01</p>
                        <button type="button" @click="kopyala('TR570020300000327370000001', 'albaraka-iban')" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-primary hover:bg-slate-100">Kopyala</button>
                        <span x-show="mesaj['albaraka-iban']" x-transition class="text-xs font-semibold text-emerald-600" style="display:none;">Kopyalandı</span>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#62868d]">Hesap No</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <p class="font-jakarta text-sm font-semibold text-primary">327370-1</p>
                        <button type="button" @click="kopyala('327370-1', 'albaraka-hesap')" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-primary hover:bg-slate-100">Kopyala</button>
                        <span x-show="mesaj['albaraka-hesap']" x-transition class="text-xs font-semibold text-emerald-600" style="display:none;">Kopyalandı</span>
                    </div>
                </div>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <img src="{{ asset('images/vakifkatilim_logo.svg') }}" alt="Vakıf Katılım" class="h-10 w-auto object-contain">
                <span class="rounded-full bg-[#eff6ff] px-3 py-1 text-xs font-semibold text-[#1e40af]">Konak Şubesi</span>
            </div>
            <h2 class="font-jakarta text-lg font-bold text-primary">VAKIF KATILIM KONAK ŞUBESİ</h2>
            <p class="mt-1 text-sm text-[#5f7480]">Kestanepazarı Öğrenci Yetiştirme Derneği</p>

            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#62868d]">IBAN</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <p class="font-jakarta text-sm font-semibold text-primary">TR68 0021 0000 0001 9947 6000 01</p>
                    <button type="button" @click="kopyala('TR680021000000019947600001', 'vakif-iban')" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-primary hover:bg-slate-100">Kopyala</button>
                    <span x-show="mesaj['vakif-iban']" x-transition class="text-xs font-semibold text-emerald-600" style="display:none;">Kopyalandı</span>
                </div>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <img src="{{ asset('images/kuveytturk_logo.svg') }}" alt="Kuveyt Türk" class="h-10 w-auto object-contain">
                <span class="rounded-full bg-[#eff6ff] px-3 py-1 text-xs font-semibold text-[#1e40af]">İzmir Şubesi</span>
            </div>
            <h2 class="font-jakarta text-lg font-bold text-primary">KUVEYT TÜRK İZMİR ŞUBESİ</h2>
            <p class="mt-1 text-sm text-[#5f7480]">Kestanepazarı Öğrenci Yetiştirme Derneği</p>

            <div class="mt-4 space-y-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#62868d]">IBAN</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <p class="font-jakarta text-sm font-semibold text-primary">TR82 0020 5000 0004 3062 2000 01</p>
                        <button type="button" @click="kopyala('TR820020500000043062200001', 'kuveyt-iban')" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-primary hover:bg-slate-100">Kopyala</button>
                        <span x-show="mesaj['kuveyt-iban']" x-transition class="text-xs font-semibold text-emerald-600" style="display:none;">Kopyalandı</span>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#62868d]">Hesap No</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <p class="font-jakarta text-sm font-semibold text-primary">430622</p>
                        <button type="button" @click="kopyala('430622', 'kuveyt-hesap')" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-primary hover:bg-slate-100">Kopyala</button>
                        <span x-show="mesaj['kuveyt-hesap']" x-transition class="text-xs font-semibold text-emerald-600" style="display:none;">Kopyalandı</span>
                    </div>
                </div>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <img src="{{ asset('images/ptt_logo.svg') }}" alt="PTT" class="h-10 w-auto object-contain">
                <span class="rounded-full bg-[#fff7ed] px-3 py-1 text-xs font-semibold text-[#c2410c]">Posta Çeki</span>
            </div>
            <h2 class="font-jakarta text-lg font-bold text-primary">PTT POSTA ÇEKİ</h2>
            <p class="mt-1 text-sm text-[#5f7480]">Kestanepazarı Öğrenci Yetiştirme Derneği</p>

            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#62868d]">Hesap No</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <p class="font-jakarta text-sm font-semibold text-primary">520 89 92</p>
                    <button type="button" @click="kopyala('5208992', 'ptt-hesap')" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-primary hover:bg-slate-100">Hesap No Kopyala</button>
                    <span x-show="mesaj['ptt-hesap']" x-transition class="text-xs font-semibold text-emerald-600" style="display:none;">Kopyalandı</span>
                </div>
            </div>
        </article>
    </div>
</section>
@endsection

@push('scripts')
<script>
function bankaKopyalama() {
    return {
        mesaj: {},
        async kopyala(deger, alan) {
            try {
                await navigator.clipboard.writeText(deger);
                this.mesaj[alan] = true;
                setTimeout(() => {
                    this.mesaj[alan] = false;
                }, 1800);
            } catch (e) {
                this.mesaj[alan] = true;
                setTimeout(() => {
                    this.mesaj[alan] = false;
                }, 1800);
            }
        }
    };
}
</script>
@endpush
