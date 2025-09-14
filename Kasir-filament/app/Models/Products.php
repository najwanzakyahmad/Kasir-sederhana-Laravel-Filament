<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Products extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';
    protected $primaryKey = 'id';
    public $incrementing = false;   // karena pakai UUID
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'category_id',
        'barcode',
        'name',
        'image',
        'sell_price',
        'tax_rate',
        'cost_price',
        'qty_on_hand',
        'is_active',
    ];

    protected $casts = [
        'sell_price'  => 'decimal:2',
        'tax_rate'    => 'decimal:2',
        'cost_price'  => 'decimal:2',
        'qty_on_hand' => 'integer',
        'is_active'   => 'boolean',
    ];

    /**
     * Relasi ke Category (satu produk milik satu kategori).
     */
    public function category()
    {
        return $this->belongsTo(Categories::class, 'category_id');
    }

    /**
     * Relasi ke SaleItem (satu produk bisa muncul di banyak item penjualan).
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItems::class, 'product_id');
    }
}
