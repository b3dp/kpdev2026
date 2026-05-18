<x-filament-panels::page>
    <form wire:submit="gonder" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap gap-3">
            <x-filament::button color="gray" type="button" wire:click="onizle" wire:loading.attr="disabled" wire:target="gonder">
                Önizle
            </x-filament::button>

            <x-filament::button color="primary" type="submit" wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed" wire:target="gonder">
                <span wire:loading.remove wire:target="gonder">Gönder</span>
                <span wire:loading wire:target="gonder">Gönderiliyor...</span>
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
