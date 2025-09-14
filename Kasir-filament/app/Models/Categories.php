<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasCustomId;

class Categories extends Model
{
    use HasFactory, SoftDeletes, HasCustomId;

    protected $table = 'categories';
    protected $primaryKey = 'id';
    public $incrementing = false;   // karena pakai UUID
    protected $keyType = 'string';

    protected $fillable = [
        'name'
    ];

    //Override getter
    protected function getCustomIdPrefix(): string    { return 'CTG'; }
    protected function getCustomIdPadLength(): int    { return 5; }

    /**
     * Relasi ke Product (satu kategori punya banyak produk).
     */
    public function products()
    {
        return $this->hasMany(Products::class, 'category_id');
    }
}
