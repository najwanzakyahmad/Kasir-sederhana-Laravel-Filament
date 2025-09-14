<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItems extends Model
{
    use HasFactory, SoftDeletes;

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
        'qty'       => 'integer',
        'price'     => 'decimal:2',
        'tax_rate'  => 'decimal:2',
        'discount'  => 'decimal:2',
        'line_total'=> 'decimal:2',
    ];
    public function sale()
    {
        return $this->belongsTo(Sales::class, 'sale_id');
    }
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
