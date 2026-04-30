<x-filament-panels::page>
    <form wire:submit="gonder" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap gap-3">
            <x-filament::button color="primary" type="submit">
                Excel'den SMS Gönder
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
