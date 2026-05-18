<x-filament-panels::page>
    @php
        $mesaj = (string) data_get($this->data, 'mesaj', '');
        $karakter = mb_strlen($mesaj, 'UTF-8');
        $smsAdedi = 0;

        if ($karakter > 0) {
            $smsAdedi = $karakter <= 155 ? 1 : (int) ceil($karakter / 149);
        }
    @endphp

    <form wire:submit="gonder" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                {{ $this->form }}

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-filament::button color="gray" type="button" wire:click="onizle" wire:loading.attr="disabled" wire:target="onizle,gonder">
                        Önizle
                    </x-filament::button>

                    <x-filament::button color="primary" type="submit" wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed" wire:target="gonder">
                        <span wire:loading.remove wire:target="gonder">Gönder</span>
                        <span wire:loading wire:target="gonder">Gönderiliyor...</span>
                    </x-filament::button>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Canlı SMS Önizleme</h3>
                    </div>

                    <div class="mx-auto w-full max-w-[320px] rounded-[2rem] border-8 border-gray-200 bg-gray-100 p-2 shadow-inner dark:border-gray-700 dark:bg-gray-800">
                        <div class="h-[520px] rounded-[1.5rem] bg-white p-3 dark:bg-gray-900">
                            <div class="mx-auto mb-3 h-1.5 w-20 rounded-full bg-gray-300 dark:bg-gray-700"></div>

                            <div class="mb-3 rounded-lg bg-gray-100 px-3 py-2 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                KESTPAZDERN
                            </div>

                            <div class="h-[390px] overflow-y-auto rounded-lg bg-gray-50 p-2 dark:bg-gray-800/60">
                                <div class="ml-auto max-w-[90%] rounded-xl bg-indigo-50 px-3 py-2 text-sm leading-6 text-gray-800 dark:bg-indigo-900/30 dark:text-gray-100">
                                    @if ($mesaj !== '')
                                        {!! nl2br(e($mesaj)) !!}
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">Mesaj yazdıkça burada görünecek.</span>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <div class="flex items-center justify-between">
                                    <span>Karakter</span>
                                    <span class="font-semibold">{{ $karakter }}</span>
                                </div>
                                <div class="mt-1 flex items-center justify-between">
                                    <span>SMS Adedi</span>
                                    <span class="font-semibold">{{ $smsAdedi }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-filament-panels::page>
