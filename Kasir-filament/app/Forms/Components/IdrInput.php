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

            // Format saat KETIK (tanpa paksa ,00) + jaga caret
            ->extraAlpineAttributes([
                'x-on:input' => RawJs::make(<<<'JS'
                const before = $el.value ?? '';
                const caret  = $el.selectionStart ?? before.length;

                // hitung jumlah digit di kiri caret sebelum diformat
                let digitsLeft = 0;
                for (let i = 0; i < caret; i++) if (/\d/.test(before[i])) digitsLeft++;

                // ambil angka & maksimal satu koma
                let raw = before.replace(/[^\d,]/g, '');
                const firstComma = raw.indexOf(',');
                if (firstComma !== -1) raw = raw.slice(0, firstComma + 1) + raw.slice(firstComma + 1).replace(/,/g, '');

                let [whole = '', frac = ''] = raw.split(',');
                whole = whole.replace(/\D/g, '');
                frac  = frac.replace(/\D/g, '').slice(0, 2);

                const formattedWhole = whole ? new Intl.NumberFormat('id-ID').format(Number(whole)) : '';
                const next = formattedWhole + (frac ? ',' + frac : '');

                if (next === before) return;

                $el.value = next;

                // posisikan caret lagi berdasar jumlah digit kiri yang sama
                const totalDigits = (next.match(/\d/g) || []).length;
                let targetDigits = Math.min(Math.max(digitsLeft, 0), totalDigits);
                let seen = 0, newPos = next.length;
                for (let i = 0; i < next.length; i++) {
                if (/\d/.test(next[i])) { seen++; if (seen === targetDigits) { newPos = i + 1; break; } }
                }
                if (targetDigits === 0) newPos = 0;
                $el.setSelectionRange(newPos, newPos);

                // re-emit supaya Livewire baca perubahan
                queueMicrotask(() => $el.dispatchEvent(new Event('input', { bubbles: true })));
                JS),

                                // INIT: format value awal jika sudah ada, tanpa memaksa ,00
                                'x-init' => RawJs::make(<<<'JS'
                let v = ($el.value ?? '').toString().trim();
                if (!v) return;

                v = v.replace(/[^\d,\.]/g, '');
                const lastComma = v.lastIndexOf(',');
                const lastDot   = v.lastIndexOf('.');
                const dotCount  = (v.match(/\./g) || []).length;

                let useCommaAsDecimal = lastComma !== -1;
                let fracAfterDot = lastDot === -1 ? 0 : v.slice(lastDot + 1).replace(/\D/g, '').length;
                let useDotAsDecimal = (!useCommaAsDecimal && lastDot !== -1 && dotCount === 1 && fracAfterDot >= 1 && fracAfterDot <= 2);

                let whole = '', frac = '';
                if (useCommaAsDecimal) {
                whole = v.slice(0, lastComma).replace(/\D/g, '');
                frac  = v.slice(lastComma + 1).replace(/\D/g, '').slice(0,2);
                } else if (useDotAsDecimal) {
                whole = v.slice(0, lastDot).replace(/\D/g, '');
                frac  = v.slice(lastDot + 1).replace(/\D/g, '').slice(0,2);
                } else {
                whole = v.replace(/\./g, '').replace(/\D/g, '');
                frac  = '';
                }

                const formattedWhole = whole ? new Intl.NumberFormat('id-ID').format(Number(whole)) : '';
                $el.value = formattedWhole + (frac ? ',' + frac : '');
                JS),
            ])

            // HYDRATE dari DB -> tampil id-ID
            ->afterStateHydrated(function (self $component, $state) {
                if ($state === null || $state === '') return;

                if (is_numeric($state)) {
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
                $useDotAsDecimal   = (!$useCommaAsDecimal && $lastDot !== false && $dotCount === 1 &&
                    ($len = strlen(preg_replace('/\D/', '', substr($s, $lastDot + 1)))) >= 1 && $len <= 2);

                if ($useCommaAsDecimal) {
                    $whole = preg_replace('/\D/', '', substr($s, 0, $lastComma));
                    $frac  = preg_replace('/\D/', '', substr($s, $lastComma + 1));
                } elseif ($useDotAsDecimal) {
                    $whole = preg_replace('/\D/', '', substr($s, 0, $lastDot));
                    $frac  = preg_replace('/\D/', '', substr($s, $lastDot + 1));
                } else {
                    $whole = preg_replace('/\D/', '', str_replace('.', '', $s));
                    $frac  = '';
                }

                $frac = substr($frac, 0, 2);
                $formattedWhole = $whole !== '' ? number_format((int) $whole, 0, ',', '.') : '0';
                $component->state($formattedWhole . ($frac !== '' ? ',' . $frac : ''));
            })

            // DEHYDRATE -> float (untuk DB & perhitungan)
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
                    $frac  = substr(preg_replace('/\D/', '', substr($s, $lastComma + 1)), 0, 2);
                    $s = ($whole === '' ? '0' : $whole) . '.' . $frac;
                } elseif ($useDotAsDecimal) {
                    $whole = preg_replace('/\D/', '', substr($s, 0, $lastDot));
                    $frac  = substr(preg_replace('/\D/', '', substr($s, $lastDot + 1)), 0, 2);
                    $s = ($whole === '' ? '0' : $whole) . '.' . $frac;
                } else {
                    $s = preg_replace('/\D/', '', str_replace('.', '', $s));
                }

                return $s === '' ? 0 : (float) $s;
            })

            // ⛔️ GANTI rule('numeric') dengan validator yang paham format IDR
            ->rule(function () {
                return function (string $attribute, $value, \Closure $fail) {
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
                        $frac  = substr(preg_replace('/\D/', '', substr($s, $lastComma + 1)), 0, 2);
                        $norm  = ($whole === '' ? '0' : $whole) . '.' . $frac;
                    } elseif ($useDotAsDecimal) {
                        $whole = preg_replace('/\D/', '', substr($s, 0, $lastDot));
                        $frac  = substr(preg_replace('/\D/', '', substr($s, $lastDot + 1)), 0, 2);
                        $norm  = ($whole === '' ? '0' : $whole) . '.' . $frac;
                    } else {
                        $norm  = preg_replace('/\D/', '', str_replace('.', '', $s));
                    }

                    if ($norm === '' || !is_numeric($norm)) {
                        $fail('Field ini harus berupa angka.');
                    }
                };
            });
    }
}
