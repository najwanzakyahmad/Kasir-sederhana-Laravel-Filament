<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasCustomId;
class SaleItems extends Model
{
    use HasFactory, SoftDeletes, HasCustomId;

    protected $table = 'sale_items';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sale_id',
        'product_id',
        'qty',
        'price',
        'tax_rate',
        'discount',
        'line_total',
    ];

    //Override getter
    protected function getCustomIdPrefix(): string    { return 'INV-PROD'; }
    protected function getCustomIdPadLength(): int    { return 5; }


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
