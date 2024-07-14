<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'WALK-IN-CUSTOMER',
                'email' => 'localcustomer@pos.com',
                'phone' => '0300-9999999',
                'address' => '123 Main St',
            ]
        ];
        foreach ($customers as $customer) {
            DB::table('customers')->insert([
                'name' => $customer['name'],
                'email' => $customer['email'],
                'phone' => $customer['phone'],
                'address' => $customer['address'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }



    }
}
