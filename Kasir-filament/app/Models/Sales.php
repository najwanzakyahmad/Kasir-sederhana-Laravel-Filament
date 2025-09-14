<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sales extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'paid_total',
        'change_due',
        'paid_at',
    ];

    protected $casts = [
        'subtotal'       => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total'      => 'decimal:2',
        'grand_total'    => 'decimal:2',
        'paid_total'     => 'decimal:2',
        'change_due'     => 'decimal:2',
        'paid_at'        => 'date',
    ];

    /**
     * Relasi ke SaleItem (satu sale bisa punya banyak item).
     */
    public function items()
    {
        return $this->hasMany(SaleItems::class, 'sale_id');
    }
}
