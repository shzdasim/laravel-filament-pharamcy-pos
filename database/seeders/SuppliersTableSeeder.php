<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuppliersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            ['id' => 1, 'name' => 'Open Purchase'],
            ['id' => 2, 'name' => 'Surgical'],
            ['id' => 3, 'name' => 'SAMEEL DISTRIBUTOR'],
            ['id' => 4, 'name' => 'Health Care Nutrition'],
            ['id' => 5, 'name' => 'Al Qamar Distributor'],
            ['id' => 6, 'name' => 'Decent White'],
            ['id' => 7, 'name' => 'Alpha Pharma'],
            ['id' => 8, 'name' => 'Noman Trader Qarshi'],
            ['id' => 9, 'name' => 'MULLER & PHIPPS PAKISTAN LTD'],
            ['id' => 10, 'name' => 'Premier Agency'],
            ['id' => 11, 'name' => 'Muller & Phiphs'],
            ['id' => 12, 'name' => 'Mobiler'],
            ['id' => 13, 'name' => 'SAMEEL PHARMACEUTICALS PVT LTD'],
            ['id' => 14, 'name' => 'Vogue'],
            ['id' => 15, 'name' => 'Ali Gohar+ Cheisi'],
            ['id' => 16, 'name' => 'Babar Medicine C'],
            ['id' => 17, 'name' => 'Bh Distributor'],
            ['id' => 18, 'name' => 'Khawaja Medicine ( A )'],
            ['id' => 19, 'name' => 'Babar Medicine  A'],
            ['id' => 20, 'name' => 'Paramount (tiger)'],
            ['id' => 21, 'name' => 'Decent Green'],
            ['id' => 22, 'name' => 'Ahbab Pharma'],
            ['id' => 23, 'name' => 'AL AZIZ DISTRIBUTORS'],
            ['id' => 24, 'name' => 'Amjad Medicine'],
            ['id' => 25, 'name' => 'NOOR DISTRIBUTORS'],
            ['id' => 26, 'name' => 'Paaris'],
            ['id' => 27, 'name' => 'Al Rehman'],
            ['id' => 28, 'name' => 'Tagline Marketing'],
            ['id' => 29, 'name' => 'UDL DISTRIBUTORS'],
            ['id' => 30, 'name' => 'Mh Enterpeises'],
            ['id' => 31, 'name' => 'Ashrafia'],
            ['id' => 32, 'name' => 'Babar Medicine  B'],
            ['id' => 33, 'name' => 'PHARMA LINKERS'],
            ['id' => 34, 'name' => 'Wrfnan'],
            ['id' => 35, 'name' => 'Parazelsus Pharma'],
            ['id' => 36, 'name' => 'Udl Pfizer&ici'],
            ['id' => 37, 'name' => 'Vikor Distrobutor'],
            ['id' => 38, 'name' => 'Farhat Ali (hilton)'],
            ['id' => 39, 'name' => 'Unique Pharma'],
            ['id' => 40, 'name' => 'Khawaja Medicine ( B )'],
            ['id' => 41, 'name' => 'MUDASSAR ENTERPRIZES'],
            ['id' => 42, 'name' => 'Dr. Naveed'],
            ['id' => 43, 'name' => 'AAA DISTRIBUTORS'],
            ['id' => 44, 'name' => 'NAEL U SHIFA DISTRIBUTORS'],
            ['id' => 45, 'name' => 'Punjab Medical Store'],
            ['id' => 46, 'name' => 'Sadaat Pharma'],
            ['id' => 47, 'name' => 'Platinium Pharma'],
            ['id' => 48, 'name' => 'Mc Pharma'],
            ['id' => 49, 'name' => 'HAFIZ MEDICINE COMPANY'],
            ['id' => 50, 'name' => 'Ibl Company'],
            ['id' => 51, 'name' => 'Mediplus Surgical'],
            ['id' => 52, 'name' => 'Udl Morinaga'],
            ['id' => 53, 'name' => 'Ak Enterprises'],
            ['id' => 54, 'name' => 'PREMIER AGENCY'],
            ['id' => 55, 'name' => 'Butt & Mughal'],
            ['id' => 56, 'name' => 'Udl Sanofi & Aventis'],
            ['id' => 57, 'name' => 'Impressive Pharma'],
            ['id' => 58, 'name' => 'HEALTH LINKERS'],
            ['id' => 59, 'name' => 'Gm Trader'],
            ['id' => 60, 'name' => 'Udl (abbot)'],
            ['id' => 61, 'name' => 'Ibl 2'],

        ];
        foreach($suppliers as $supplier){
            DB::table('suppliers')->insert([
                'name' => $supplier['name'],
                'address' => $supplier['address'],
                'phone' => $supplier['phone'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
