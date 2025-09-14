<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('status');
            $table->decimal('subtotal');
            $table->decimal('discount_total');
            $table->decimal('tax_total');
            $table->decimal('grand_total');
            $table->decimal('paid_total');
            $table->decimal('change_due');
            $table->date('paid_at');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
