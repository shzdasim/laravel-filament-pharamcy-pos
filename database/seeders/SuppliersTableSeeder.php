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
            [
                'name' => 'SAMEEL DISTRIBUTORS SARGODHA',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'MOEEN ENTERPRIESES SARGODHA',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'SAMEEL PHARMACEUTICAL SARGODHA',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'AL AZIZ DISTRIBUTOR',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'NAEEL U SHIFA DISTRIBUTOR',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'MUDASSAR ENTERPRISES',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'PRIEMIER AGENCY PVT LTD',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'NOOR DISTRIBUTORS',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'AAA DISTRIBUTORS',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'HAFIZ MEDICINE PHARMA',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'HEALTH LINKERS',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'PARADISE DISTRIBUTORS',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'HEALTH ALLIENCE',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'PHARMA LINKERS',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'RANA BROTHERS',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'MULLER & PHIPPS PVT LTD',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],
            [
                'name' => 'ALI GOHAR AGENCY PVT LTD',
                'address' => 'SARGODHA',
                'phone' => '0300-0000000',
            ],

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
