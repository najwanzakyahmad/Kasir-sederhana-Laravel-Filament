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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // definisi kolom foreign key dulu
            $table->uuid('category_id');

            $table->longText('barcode');
            $table->string('name');
            $table->string('image')->nullable();
            $table->decimal('sell_price', 15, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('cost_price', 15, 2);
            $table->integer('qty_on_hand')->default(0);
            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            // baru tambahkan foreign key
            $table->foreign('category_id')
                  ->references('id')->on('categories')
                  ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
