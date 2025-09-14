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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('sale_id');
            $table->string('product_id');

            $table->integer('qty');
            $table->decimal('price', 15, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('sale_id')
                  ->references('id')->on('sales')
                  ->restrictOnDelete();

            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
