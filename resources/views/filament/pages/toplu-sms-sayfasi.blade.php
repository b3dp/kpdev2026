<x-filament-panels::page>
    <form wire:submit="gonder" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap gap-3">
            <x-filament::button color="gray" type="button" wire:click="onizle">
                Önizle
            </x-filament::button>

            <x-filament::button color="primary" type="submit">
                Gönder
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
