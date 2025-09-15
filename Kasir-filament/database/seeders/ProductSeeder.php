<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Categories;
use App\Models\Products;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Categories::pluck('id', 'name');

        // Daftar produk per kategori (5 item per kategori)
        $productSamples = [
            'Bahan Dapur' => [
                ['Beras 5 Kg', 'BD001', 65000, 60000, 0, 20],
                ['Minyak Goreng 1 L', 'BD002', 17000, 15000, 0, 50],
                ['Gula Pasir 1 Kg', 'BD003', 14000, 12000, 0, 40],
                ['Garam Dapur 500g', 'BD004', 4000, 3000, 0, 30],
                ['Tepung Terigu 1 Kg', 'BD005', 12000, 10000, 0, 35],
            ],
            'Makanan Ringan' => [
                ['Keripik Singkong', 'MR001', 8000, 6000, 0, 40],
                ['Biskuit Coklat', 'MR002', 10000, 8000, 0, 25],
                ['Kacang Atom', 'MR003', 9000, 7000, 0, 20],
                ['Permen Mint', 'MR004', 3000, 2000, 0, 100],
                ['Wafer Keju', 'MR005', 7000, 5000, 0, 30],
            ],
            'Minuman' => [
                ['Air Mineral 600ml', 'MN001', 4000, 3000, 0, 100],
                ['Teh Botol 350ml', 'MN002', 5000, 4000, 0, 60],
                ['Kopi Botol 250ml', 'MN003', 8000, 6500, 0, 40],
                ['Soda Kaleng', 'MN004', 9000, 7500, 0, 30],
                ['Jus Buah Kotak', 'MN005', 12000, 10000, 0, 25],
            ],
            'Sembako' => [
                ['Telur Ayam 1 Kg', 'SM001', 30000, 28000, 0, 50],
                ['Bawang Merah 1 Kg', 'SM002', 35000, 33000, 0, 20],
                ['Bawang Putih 1 Kg', 'SM003', 28000, 26000, 0, 25],
                ['Kedelai 1 Kg', 'SM004', 15000, 13000, 0, 15],
                ['Jagung Pipil 1 Kg', 'SM005', 10000, 8000, 0, 30],
            ],
            'Produk Susu' => [
                ['Susu UHT 1L', 'PS001', 18000, 16000, 0, 25],
                ['Yogurt Cup', 'PS002', 9000, 7500, 0, 40],
                ['Keju Slice', 'PS003', 20000, 18000, 0, 15],
                ['Mentega 200g', 'PS004', 12000, 10000, 0, 30],
                ['Krimer Kental Manis', 'PS005', 11000, 9000, 0, 35],
            ],
            'Peralatan Mandi' => [
                ['Sabun Mandi Batang', 'PM001', 4000, 3000, 0, 60],
                ['Shampoo 100ml', 'PM002', 12000, 10000, 0, 30],
                ['Sikat Gigi', 'PM003', 7000, 5000, 0, 40],
                ['Pasta Gigi 100g', 'PM004', 15000, 13000, 0, 35],
                ['Sampo Sachet', 'PM005', 1000, 700, 0, 200],
            ],
            'Peralatan Cuci' => [
                ['Deterjen Bubuk 1 Kg', 'PC001', 18000, 16000, 0, 40],
                ['Sabun Cuci Piring 500ml', 'PC002', 14000, 12000, 0, 35],
                ['Spons Cuci', 'PC003', 5000, 3000, 0, 50],
                ['Pewangi Pakaian 500ml', 'PC004', 15000, 13000, 0, 20],
                ['Sikat Baju', 'PC005', 10000, 8000, 0, 25],
            ],
            'Peralatan Rumah Tangga' => [
                ['Lampu LED 10W', 'PR001', 25000, 22000, 0, 30],
                ['Piring Keramik', 'PR002', 12000, 10000, 0, 40],
                ['Gelas Kaca', 'PR003', 10000, 8000, 0, 35],
                ['Sendok Stainless', 'PR004', 5000, 3000, 0, 60],
                ['Sapu Lantai', 'PR005', 20000, 18000, 0, 15],
            ],
            'Obat & Kesehatan' => [
                ['Paracetamol Strip', 'OK001', 6000, 4000, 0, 50],
                ['Minyak Kayu Putih 60ml', 'OK002', 18000, 15000, 0, 20],
                ['Vitamin C 500mg', 'OK003', 25000, 20000, 0, 25],
                ['Antiseptik Cair 100ml', 'OK004', 12000, 10000, 0, 30],
                ['Plester Luka', 'OK005', 5000, 3000, 0, 40],
            ],
            'Lainnya' => [
                ['Baterai AA 2pcs', 'LN001', 12000, 10000, 0, 40],
                ['Pulpen Biru', 'LN002', 5000, 3000, 0, 60],
                ['Buku Tulis 40 Lembar', 'LN003', 7000, 5000, 0, 30],
                ['Korek Api Gas', 'LN004', 8000, 6000, 0, 25],
                ['Kantong Plastik 1 Kg', 'LN005', 15000, 12000, 0, 35],
            ],
        ];

        foreach ($productSamples as $categoryName => $items) {
            foreach ($items as [$name, $barcode, $sell, $cost, $tax, $qty]) {
                Products::create([
                    'category_id' => $categories[$categoryName],
                    'barcode'     => $barcode,
                    'name'        => $name,
                    'image'       => null, // kosong
                    'sell_price'  => $sell,
                    'tax_rate'    => $tax,
                    'cost_price'  => $cost,
                    'qty_on_hand' => $qty,
                    'is_active'   => true,
                ]);
            }
        }
    }
}
