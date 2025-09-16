<?php

namespace App\Forms\Components;

use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

class IdrInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            // Tampilkan “Rp ” di kiri input (hanya UI)
            ->prefix('Rp')
            // Format ribuan saat user mengetik — TANPA bergantung plugin mask
            ->extraAlpineAttributes([
                'x-data' => '{}',
                'x-on:input' => \Filament\Support\RawJs::make(<<<'JS'
            const before = $el.value;
            const digits = before.replace(/\D/g, '');
            const formatted = digits ? new Intl.NumberFormat('id-ID').format(digits) : '';
            if (formatted !== before) {
            $el.value = formatted;
            // Re-emit agar Livewire/Filament restart debouncenya dengan nilai yang sudah diformat
            $nextTick(() => $el.dispatchEvent(new Event('input', { bubbles: true })));
            }
            JS),
                'x-init' => \Filament\Support\RawJs::make(<<<'JS'
            const d = ($el.value ?? '').toString().replace(/\D/g, '');
            if (d) { $el.value = new Intl.NumberFormat('id-ID').format(d); }
            JS),
            ])

            // Simpan ke state: ANGKA POLOS (tanpa titik/koma/space/Rp)
            ->dehydrateStateUsing(fn ($state) => $state === null
                ? null
                : (float) preg_replace('/\D/', '', (string) $state)
            )
            // Validasi server-side tetap angka
            ->rule('numeric');

        // PENTING:
        // - JANGAN tambahkan ->numeric() di pemanggil (type=number mematikan format).
        // - Kalau butuh 2 desimal, kasih tahu — aku kirim versi desimalnya.
    }
}
