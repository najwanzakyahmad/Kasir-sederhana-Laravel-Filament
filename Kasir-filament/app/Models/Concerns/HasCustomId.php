<?php
// app/Models/Concerns/HasCustomId.php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;

trait HasCustomId
{
    // Konfigurasi default (bisa dioverride per-model)
    protected static string $customIdField      = 'id';
    protected static string $customIdPrefix     = '';     // contoh: 'CTG', 'PROD', 'USER'
    protected static string $customIdSeparator  = '-';
    protected static int    $customIdPadLength  = 5;      // contoh: 00001
    protected static string $customIdPadChar    = '0';

    protected static function bootHasCustomId(): void
    {
        static::creating(function ($model) {
            $field = $model->getCustomIdField();

            // Jika sudah diisi manual, skip
            if (!empty($model->{$field})) {
                return;
            }

            $prefix    = $model->getCustomIdPrefix();
            $sep       = $model->getCustomIdSeparator();
            $padLen    = $model->getCustomIdPadLength();
            $padChar   = $model->getCustomIdPadChar();

            $query = $model->newQuery();

            // Jika model pakai SoftDeletes, sertakan trashed agar urutan tetap berlanjut
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query->withTrashed();
            }

            // Ambil ID terakhir sesuai prefix (contoh: CTG-xxxxx)
            $like = $prefix !== '' ? $prefix . $sep . '%' : '%';
            $lastId = $query
                ->where($field, 'like', $like)
                ->orderBy($field, 'desc')
                ->value($field);

            $nextNumber = 1;
            if ($lastId) {
                // Ambil angka di belakang prefix+separator
                $pattern = '/^' . preg_quote($prefix . $sep, '/') . '/';
                $numberPart = (int) preg_replace($pattern, '', $lastId);
                $nextNumber = $numberPart + 1;
            }

            $model->{$field} = ($prefix ? ($prefix . $sep) : '')
                . str_pad((string) $nextNumber, $padLen, $padChar, STR_PAD_LEFT);
        });
    }

    // Getter yang bisa dioverride per-model jika perlu lebih fleksibel
    protected function getCustomIdField(): string     { return static::$customIdField; }
    protected function getCustomIdPrefix(): string    { return static::$customIdPrefix; }
    protected function getCustomIdSeparator(): string { return static::$customIdSeparator; }
    protected function getCustomIdPadLength(): int    { return static::$customIdPadLength; }
    protected function getCustomIdPadChar(): string   { return static::$customIdPadChar; }
}
