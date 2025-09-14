<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleItems extends Model
{
    use HasFactory;

    protected $table = 'sale_items';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'sale_id',
        'product_id',
        'qty',
        'price',
        'tax_rate',
        'discount',
        'line_total',
    ];

    protected $casts = [
        'qty'        => 'integer',
        'price'      => 'decimal:2',
        'tax_rate'   => 'decimal:2',
        'discount'   => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    /**
     * Relasi ke Sale (banyak item milik satu sale).
     */
    public function sale()
    {
        return $this->belongsTo(Sales::class, 'sale_id');
    }

    /**
     * Relasi ke Product (satu item merujuk satu product).
     */
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
