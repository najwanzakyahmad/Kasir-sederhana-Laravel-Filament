<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\InventoryService;
use App\Models\Concerns\HasCustomId;   // <— pakai trait-mu

class Sales extends Model
{
    use HasFactory, SoftDeletes, HasCustomId; // <— aktifkan

    // id string non-increment
    public $incrementing = false;
    protected $keyType   = 'string';
    protected $guarded = [];

    // konfigurasi ID kustom
    protected function getCustomIdPrefix(): string   { return 'SALE'; } // SALE000001, dst
    protected function getCustomIdPadLength(): int   { return 5; }      // 6 digit

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_total'  => 'decimal:2',
        'tax_total'       => 'decimal:2',
        'grand_total'     => 'decimal:2',
        'paid_total'      => 'decimal:2',
        'change_due'      => 'decimal:2',
        'paid_at'         => 'datetime',
        'stock_applied'   => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Sales $sale) {
            if ($sale->status === 'paid') {
                if (empty($sale->paid_at)) {
                    $sale->paid_at = now()->toDateString(); // simpan YYYY-MM-DD
                }
            } else {
                $sale->paid_at = null;
            }
        }).
        static::updated(function (Sales $sale) {
            if (! $sale->wasChanged('status')) return;

            $old = $sale->getOriginal('status');
            $new = $sale->status;

            if ($old !== 'paid' && $new === 'paid' && ! $sale->stock_applied) {
                InventoryService::applyForSale($sale);
            }
            if ($old === 'paid' && $new !== 'paid' && $sale->stock_applied) {
                InventoryService::revertForSale($sale);
            }
        });

        static::deleted(function (Sales $sale) {
            if ($sale->stock_applied) {
                InventoryService::revertForSale($sale);
            }
        });
    }

    public function items()
    {
        return $this->hasMany(SaleItems::class, 'sale_id', 'id');
    }
}
