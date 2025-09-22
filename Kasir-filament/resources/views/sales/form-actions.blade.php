<div class="mt-4 flex items-center justify-end gap-3">
    {{-- Cancel: tidak submit form, panggil method halaman --}}
    <x-filament::button type="button" wire:click="cancel" color="gray">
        Cancel
    </x-filament::button>

    {{-- Create & create another: panggil method halaman --}}
    <x-filament::button type="button" wire:click="createAnother" outlined>
        Create & create another
    </x-filament::button>

    {{-- Create: submit form (akan memanggil handler create() bawaan halaman) --}}
    <x-filament::button type="submit" color="primary">
        Create
    </x-filament::button>
</div>
