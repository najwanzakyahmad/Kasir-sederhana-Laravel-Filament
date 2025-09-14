<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categories extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories';
    protected $primaryKey = 'id';
    public $incrementing = false;   // karena pakai UUID
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
    ];

    /**
     * Relasi ke Product (satu kategori punya banyak produk).
     */
    public function products()
    {
        return $this->hasMany(Products::class, 'category_id');
    }
}
