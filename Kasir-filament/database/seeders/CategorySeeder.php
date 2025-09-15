<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Categories;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Bahan Dapur',
            'Makanan Ringan',
            'Minuman',
            'Sembako',
            'Produk Susu',
            'Peralatan Mandi',
            'Peralatan Cuci',
            'Peralatan Rumah Tangga',
            'Obat & Kesehatan',
            'Lainnya',
        ];

        foreach ($categories as $category) {
            Categories::create(['name' => $category]);
        }
    }
}
