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
            ->prefix('Rp')

            // === HYDRATE: nilai dari DB (4000.00 / 4.000,00 / 4000) -> tampil "4.000,00" ===
            ->afterStateHydrated(function (self $component, $state) {
                if ($state === null || $state === '') return;

                if (is_numeric($state)) {
                    // dari cast decimal:2 -> format id-ID 2 desimal
                    $component->state(number_format((float) $state, 2, ',', '.'));
                    return;
                }

                $s = (string) $state;
                $s = str_replace(['Rp', ' ', "\xC2\xA0"], '', $s);
                $s = preg_replace('/[^0-9\.,]/', '', $s);

                $lastComma = strrpos($s, ',');
                $lastDot   = strrpos($s, '.');
                $dotCount  = substr_count($s, '.');

                $useCommaAsDecimal = $lastComma !== false;
                // Titik dianggap desimal hanya jika TEPAT satu titik & 1–2 digit setelahnya
                $useDotAsDecimal   = (!$useCommaAsDecimal && $lastDot !== false && $dotCount === 1
                    && ($len = strlen(preg_replace('/\D/', '', substr($s, $lastDot + 1)))) >= 1 && $len <= 2);

                if ($useCommaAsDecimal) {
                    $whole = preg_replace('/\D/', '', substr($s, 0, $lastComma));
                    $frac  = preg_replace('/\D/', '', substr($s, $lastComma + 1));
                } elseif ($useDotAsDecimal) {
                    $whole = preg_replace('/\D/', '', substr($s, 0, $lastDot));
                    $frac  = preg_replace('/\D/', '', substr($s, $lastDot + 1));
                } else {
                    // Semua titik adalah ribuan -> buang
                    $whole = preg_replace('/\D/', '', str_replace('.', '', $s));
                    $frac  = '';
                }

                $frac = substr($frac, 0, 2);
                $formattedWhole = $whole !== '' ? number_format((int) $whole, 0, ',', '.') : '0';
                $component->state($formattedWhole . ($frac !== '' ? ',' . $frac : ''));
            })

            // === KETIK: dukung '.' atau ','; tampilkan selalu dengan koma sebagai desimal ===
            ->extraAlpineAttributes([
                'x-data' => '{}',
                'x-on:input' => RawJs::make(<<<'JS'
const before = $el.value ?? '';
let raw = before.replace(/[^0-9\.,]/g, '');

const lastComma = raw.lastIndexOf(',');
const lastDot   = raw.lastIndexOf('.');
const dotCount  = (raw.match(/\./g) || []).length;

let useCommaAsDecimal = lastComma !== -1;
let fracAfterDotLen   = lastDot === -1 ? 0 : raw.slice(lastDot + 1).replace(/\D/g, '').length;
let useDotAsDecimal   = (!useCommaAsDecimal && lastDot !== -1 && dotCount === 1 && fracAfterDotLen >= 1 && fracAfterDotLen <= 2);

let whole = raw, frac = '';
if (useCommaAsDecimal) {
  whole = raw.slice(0, lastComma).replace(/\D/g, '');
  frac  = raw.slice(lastComma + 1).replace(/\D/g, '').slice(0, 2);
} else if (useDotAsDecimal) {
  whole = raw.slice(0, lastDot).replace(/\D/g, '');
  frac  = raw.slice(lastDot + 1).replace(/\D/g, '').slice(0, 2);
} else {
  // titik dianggap ribuan => hapus semua titik
  whole = raw.replace(/\./g, '').replace(/\D/g, '');
  frac  = '';
}

const formattedWhole = whole ? new Intl.NumberFormat('id-ID').format(Number(whole)) : '0';
const next = formattedWhole + (frac ? ',' + frac : '');

if (next !== before) {
  $el.value = next;
  $nextTick(() => $el.dispatchEvent(new Event('input', { bubbles: true })));
}
JS),
                'x-init' => RawJs::make(<<<'JS'
let v = ($el.value ?? '').toString().trim();
if (!v) return;

let raw = v.replace(/[^0-9\.,]/g, '');
const lastComma = raw.lastIndexOf(',');
const lastDot   = raw.lastIndexOf('.');
const dotCount  = (raw.match(/\./g) || []).length;

let useCommaAsDecimal = lastComma !== -1;
let fracAfterDotLen   = lastDot === -1 ? 0 : raw.slice(lastDot + 1).replace(/\D/g, '').length;
let useDotAsDecimal   = (!useCommaAsDecimal && lastDot !== -1 && dotCount === 1 && fracAfterDotLen >= 1 && fracAfterDotLen <= 2);

let whole = raw, frac = '';
if (useCommaAsDecimal) {
  whole = raw.slice(0, lastComma).replace(/\D/g, '');
  frac  = raw.slice(lastComma + 1).replace(/\D/g, '').slice(0, 2);
} else if (useDotAsDecimal) {
  whole = raw.slice(0, lastDot).replace(/\D/g, '');
  frac  = raw.slice(lastDot + 1).replace(/\D/g, '').slice(0, 2);
} else {
  whole = raw.replace(/\./g, '').replace(/\D/g, '');
  frac  = '';
}

const formattedWhole = whole ? new Intl.NumberFormat('id-ID').format(Number(whole)) : '0';
$el.value = formattedWhole + (frac ? ',' + frac : '');
JS),
            ])

            // === SAVE: normalisasi ke float (1234,56 / 1.234,56 / 1234.56 -> 1234.56) ===
            ->dehydrateStateUsing(function ($state) {
                if ($state === null || $state === '') return 0;

                $s = str_replace(['Rp', ' ', "\xC2\xA0"], '', (string) $state);
                $s = preg_replace('/[^0-9\.,]/', '', $s);

                $lastComma = strrpos($s, ',');
                $lastDot   = strrpos($s, '.');
                $dotCount  = substr_count($s, '.');

                $useCommaAsDecimal = $lastComma !== false;
                $fracLenAfterDot   = $lastDot === false ? 0 : strlen(preg_replace('/\D/', '', substr($s, $lastDot + 1)));
                $useDotAsDecimal   = (!$useCommaAsDecimal && $lastDot !== false && $dotCount === 1 && $fracLenAfterDot >= 1 && $fracLenAfterDot <= 2);

                if ($useCommaAsDecimal) {
                    $whole = preg_replace('/\D/', '', substr($s, 0, $lastComma));
                    $frac  = preg_replace('/\D/', '', substr($s, $lastComma + 1));
                    $frac  = substr($frac, 0, 2);
                    $s = ($whole === '' ? '0' : $whole) . '.' . $frac;
                } elseif ($useDotAsDecimal) {
                    $whole = preg_replace('/\D/', '', substr($s, 0, $lastDot));
                    $frac  = preg_replace('/\D/', '', substr($s, $lastDot + 1));
                    $frac  = substr($frac, 0, 2);
                    $s = ($whole === '' ? '0' : $whole) . '.' . $frac;
                } else {
                    // semua titik = ribuan
                    $s = preg_replace('/\D/', '', str_replace('.', '', $s));
                }

                return $s === '' ? 0 : (float) $s;
            })

            // === VALIDASI: ganti 'numeric' dengan validator yang paham IDR ===
            ->rule(function () {
                return function (string $attribute, $value, \Closure $fail) {
                    // biarkan rule 'required' yg lain yang memeriksa kekosongan
                    if ($value === null || $value === '') return;

                    $s = str_replace(['Rp', ' ', "\xC2\xA0"], '', (string) $value);
                    $s = preg_replace('/[^0-9\.,]/', '', $s);

                    $lastComma = strrpos($s, ',');
                    $lastDot   = strrpos($s, '.');
                    $dotCount  = substr_count($s, '.');

                    $useCommaAsDecimal = $lastComma !== false;
                    $fracLenAfterDot   = $lastDot === false ? 0 : strlen(preg_replace('/\D/', '', substr($s, $lastDot + 1)));
                    $useDotAsDecimal   = (!$useCommaAsDecimal && $lastDot !== false && $dotCount === 1 && $fracLenAfterDot >= 1 && $fracLenAfterDot <= 2);

                    if ($useCommaAsDecimal) {
                        $whole = preg_replace('/\D/', '', substr($s, 0, $lastComma));
                        $frac  = preg_replace('/\D/', '', substr($s, $lastComma + 1));
                        $norm  = ($whole === '' ? '0' : $whole) . '.' . substr($frac, 0, 2);
                    } elseif ($useDotAsDecimal) {
                        $whole = preg_replace('/\D/', '', substr($s, 0, $lastDot));
                        $frac  = preg_replace('/\D/', '', substr($s, $lastDot + 1));
                        $norm  = ($whole === '' ? '0' : $whole) . '.' . substr($frac, 0, 2);
                    } else {
                        $norm = preg_replace('/\D/', '', str_replace('.', '', $s));
                    }

                    if ($norm === '' || !is_numeric($norm)) {
                        $fail('Field ini harus berupa angka.');
                    }
                };
            });
    }
}
