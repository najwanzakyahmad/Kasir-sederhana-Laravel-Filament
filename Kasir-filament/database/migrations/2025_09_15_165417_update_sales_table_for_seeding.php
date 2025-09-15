<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Lebarkan precision nominal
            $table->decimal('subtotal', 15, 2)->change();
            $table->decimal('discount_total', 15, 2)->change();
            $table->decimal('tax_total', 15, 2)->change();
            $table->decimal('grand_total', 15, 2)->change();
            $table->decimal('paid_total', 15, 2)->change();
            $table->decimal('change_due', 15, 2)->change();

            // paid_at bisa null (untuk UNPAID)
            $table->date('paid_at')->nullable()->change();

            // Tambahkan timestamps kalau belum ada
            if (!Schema::hasColumn('sales', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // optional: rollback ke default kecil (tidak wajib diisi)
            // $table->decimal('subtotal', 8, 2)->change();
            // ...
            // $table->date('paid_at')->nullable(false)->change();
            // $table->dropTimestamps();
        });
    }
};
