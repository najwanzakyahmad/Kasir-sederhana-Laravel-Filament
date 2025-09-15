<?php
// app/Models/Concerns/HasCustomId.php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

trait HasCustomId
{
    protected static string $customIdField      = 'id';
    protected static string $customIdPrefix     = '';
    protected static string $customIdSeparator  = '-';
    protected static int    $customIdPadLength  = 5;
    protected static string $customIdPadChar    = '0';

    protected static function bootHasCustomId(): void
    {
        static::creating(function (Model $model) {
            $field = $model->getCustomIdField();

            if (!empty($model->{$field})) {
                return; // sudah diisi manual
            }

            $prefix  = $model->getCustomIdPrefix();
            $sep     = $model->getCustomIdSeparator();
            $padLen  = $model->getCustomIdPadLength();
            $padChar = $model->getCustomIdPadChar();

            $like = $prefix !== '' ? "{$prefix}{$sep}%" : '%';

            $query = $model->newQuery();

            // Sertakan soft-deleted bila model gunakan SoftDeletes
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query->withTrashed();
            }

            // Ambil MAX angka suffix secara numerik, bukan "last row" string
            $maxNum = (int) $query
                ->where($field, 'like', $like)
                ->max(DB::raw("CAST(SUBSTRING_INDEX($field, '{$sep}', -1) AS UNSIGNED)"));

            $next = $maxNum + 1;

            $model->{$field} = ($prefix !== '' ? "{$prefix}{$sep}" : '')
                . str_pad((string) $next, $padLen, $padChar, STR_PAD_LEFT);
        });
    }

    protected function getCustomIdField(): string     { return static::$customIdField; }
    protected function getCustomIdPrefix(): string    { return static::$customIdPrefix; }
    protected function getCustomIdSeparator(): string { return static::$customIdSeparator; }
    protected function getCustomIdPadLength(): int    { return static::$customIdPadLength; }
    protected function getCustomIdPadChar(): string   { return static::$customIdPadChar; }
}
