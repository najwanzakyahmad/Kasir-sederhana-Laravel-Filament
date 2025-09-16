<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        TextInput::macro('asIdr', function (int $decimals = 0, string $prefix = 'Rp') {
        /** @var \Filament\Forms\Components\TextInput $this */
        return $this
            ->prefix($prefix) // hanya tampilan, tidak mengubah state
            // Mask JS bawaan Filament v4 (pakai Alpine helpers)
            ->mask(RawJs::make('$money($input, ".", ",", ' . $decimals . ')'))
            // Buang pemisah dari nilai yang disimpan agar state numeric murni
            ->stripCharacters(['.', ',', ' '])
            // Validasi di server (boleh ditimpa/ditambah di field masing2)
            ->rule('numeric');
            // Penting: JANGAN tambah ->numeric() di field pemanggil (itu bikin <input type="number"> dan mask tidak terlihat)
    });
    }
}
