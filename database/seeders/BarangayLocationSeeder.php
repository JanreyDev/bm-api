<?php

namespace Database\Seeders;

use App\Models\BarangayLocation;
use Illuminate\Database\Seeder;

class BarangayLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'Asinan', 'is_active' => true, 'sort_order' => 10],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'Banicain', 'is_active' => true, 'sort_order' => 20],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'Barretto', 'is_active' => true, 'sort_order' => 30],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'East Bajac-bajac', 'is_active' => true, 'sort_order' => 40],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'East Tapinac', 'is_active' => true, 'sort_order' => 50],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'Gordon Heights', 'is_active' => true, 'sort_order' => 60],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'Kalaklan', 'is_active' => true, 'sort_order' => 70],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'Mabayuan', 'is_active' => true, 'sort_order' => 80],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'New Cabalan', 'is_active' => true, 'sort_order' => 90],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'New Ilalim', 'is_active' => true, 'sort_order' => 100],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'New Kababae', 'is_active' => true, 'sort_order' => 110],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'New Kalalake', 'is_active' => true, 'sort_order' => 120],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'Old Cabalan', 'is_active' => true, 'sort_order' => 130],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'Pag-asa', 'is_active' => true, 'sort_order' => 140],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'Santa Rita', 'is_active' => true, 'sort_order' => 150],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'West Bajac-bajac', 'is_active' => true, 'sort_order' => 160],
            ['province' => 'Zambales', 'city_municipality' => 'City of Olongapo', 'barangay' => 'West Tapinac', 'is_active' => true, 'sort_order' => 170],
            ['province' => 'Zambales', 'city_municipality' => 'Subic', 'barangay' => 'Calapacuan', 'is_active' => true, 'sort_order' => 180],
            ['province' => 'Zambales', 'city_municipality' => 'Subic', 'barangay' => 'Baraca-Camachile', 'is_active' => true, 'sort_order' => 190],
            ['province' => 'Bataan', 'city_municipality' => 'Balanga City', 'barangay' => 'Bagumbayan', 'is_active' => true, 'sort_order' => 200],
            ['province' => 'Bataan', 'city_municipality' => 'Balanga City', 'barangay' => 'Poblacion', 'is_active' => true, 'sort_order' => 210],
        ];

        foreach ($rows as $row) {
            BarangayLocation::query()->updateOrCreate(
                [
                    'province' => $row['province'],
                    'city_municipality' => $row['city_municipality'],
                    'barangay' => $row['barangay'],
                ],
                [
                    'is_active' => $row['is_active'],
                    'sort_order' => $row['sort_order'],
                ],
            );
        }
    }
}

