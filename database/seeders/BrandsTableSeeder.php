<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandsTableSeeder extends Seeder
{
    public function run()
    {
        $brands = [
            [
                'name' => 'Abbott Laboratories',
                'image' => 'images/brands/Abbott Laboratories.png'
            ],
            [
                'name' => 'Getz Pharma',
                'image' => 'images/brands/Getz Pharma.png'
            ],
            [
                'name' => 'Hilton Pharma',
                'image' => 'images/brands/Hilton Pharma.jpeg'
            ],
            [
                'name' => 'GlaxoSmithKline',
                'image' => 'images/brands/GlaxoSmithKline.svg'
            ],
            [
                'name' => 'Ferozsons Laboratories',
                'image' => 'images/brands/Ferozsons Laboratories.jpeg'
            ],
            [
                'name' => 'PharmEvo',
                'image' => 'images/brands/PharmEvo.png'
            ],
            [
                'name' => 'Sami Pharmaceuticals',
                'image' => 'images/brands/Sami Pharmaceuticals.png'
            ],
            [
                'name' => 'Sanofi Aventis',
                'image' => 'images/brands/Sanofi Aventis.png'
            ],
            [
                'name' => 'Pfizer',
                'image' => 'images/brands/Pfizer.png'
            ],
            [
                'name' => 'The Searle Company',
                'image' => 'images/brands/The Searle Company.png'
            ],
            [
                'name' => 'Highnoon Laboratories',
                'image' => 'images/brands/Highnoon Laboratories.jpeg'
            ],
            [
                'name' => 'Martin Dow',
                'image' => 'images/brands/Martin Dow.png'
            ],
            [
                'name' => 'Novartis Pharma',
                'image' => 'images/brands/Novartis Pharma.png'
            ],
            [
                'name' => 'Aspen Pharma',
                'image' => 'images/brands/Aspen Pharma.png'
            ],
            [
                'name' => 'Merck Pakistan',
                'image' => 'images/brands/Merck Pakistan.jpeg'
            ],
            [
                'name' => 'Atco Laboratories',
                'image' => 'images/brands/Atco Laboratories.png'
            ],
            [
                'name' => 'Platinum Pharmaceutical',
                'image' => 'images/brands/Platinum Pharmaceutical.png'
            ],
            [
                'name' => 'Bosch Pharmaceuticals',
                'image' => 'images/brands/Bosch Pharmaceuticals.png'
            ],
            [
                'name' => 'Brooks Pharma',
                'image' => 'images/brands/Brooks Pharma.png'
            ],
            [
                'name' => 'Genix Pharma',
                'image' => 'images/brands/Genix Pharma.png'
            ],
            [
                'name' => 'Wilson\'s Pharmaceuticals',
                'image' => 'images/brands/Wilsons Pharmaceuticals.png'
            ],
            [
                'name' => 'Premier Pharmaceuticals',
                'image' => 'images/brands/Premier Pharmaceuticals.png'
            ],
            [
                'name' => 'Schazoo Zaka',
                'image' => 'images/brands/Schazoo Zaka.jpeg'
            ],
            [
                'name' => 'Platinum Pharma',
                'image' => 'images/brands/Platinum Pharmaceutical.png'
            ],
            [
                'name' => 'Indus Pharma',
                'image' => 'images/brands/Indus Pharma.png'
            ],
            [
                'name' => 'Helicon Pharmaceutical',
                'image' => 'images/brands/Helicon Pharmaceutical.png'
            ],
            [
                'name' => 'Zafa Pharmaceutical',
                'image' => 'images/brands/Zafa Pharmaceutical.png'
            ],
            [
                'name' => 'Scottmann',
                'image' => 'images/brands/Scottmann.png'
            ],
            [
                'name' => 'Roche',
                'image' => 'images/brands/Roche.png'
            ],
            [
                'name' => 'Boehringer Ingelheim',
                'image' => 'images/brands/Boehringer Ingelheim.png'
            ],
            [
                'name' => 'Pharma Health',
                'image' => 'images/brands/Pharma Health.webp'
            ],
            [
                'name' => 'MediOne',
                'image' => 'images/brands/MediOne.png'
            ],
            [
                'name' => 'Bio-Labs',
                'image' => 'images/brands/Bio-Labs.webp'
            ],
            [
                'name' => 'AGP Limited',
                'image' => 'images/brands/AGP Limited.png'
            ],
            [
                'name' => 'Pacific Pharmaceuticals',
                'image' => 'images/brands/Pacific Pharmaceuticals.png'
            ],
            [
                'name' => 'Tabros Pharma',
                'image' => 'images/brands/Tabros Pharma.jpeg'
            ],
            [
                'name' => 'Pharmatec Pakistan',
                'image' => 'images/brands/Pharmatec Pakistan.png'
            ],
            [
                'name' => 'Selmore Pharma',
                'image' => 'images/brands/Selmore Pharma.png'
            ],
            [
                'name' => 'Ali Gohar Pharmaceuticals',
                'image' => 'images/brands/Ali Gohar Pharmaceuticals.jpeg'
            ],
            [
                'name' => 'Chiesi Pakistan',
                'image' => 'images/brands/Chiesi Pakistan.png'
            ],
            [
                'name' => 'Haleon Pakistan',
                'image' => 'images/brands/Haleon Pakistan.jpeg'
            ],
            [
                'name' => 'Scilife Pharma',
                'image' => 'images/brands/Scilife Pharma.png'
            ],
            [
                'name' => 'Adamjee Pharma',
                'image' => 'images/brands/Adamjee Pharma.png'
            ],
            [
                'name' => 'Shaigan Pharmaceuticals',
                'image' => 'images/brands/Shaigan Pharmaceuticals.jpeg'
            ],
            [
                'name' => 'Nabi Qasim Industries',
                'image' => 'images/brands/Nabi Qasim Industries.png'
            ],
            [
                'name' => 'Macter International',
                'image' => 'images/brands/Macter International.jpeg'
            ],
            [
                'name' => 'Maple Pharma',
                'image' => 'images/brands/Maple Pharma.jpeg'
            ],
            [
                'name' => 'S.J. & G. Fazal Elahi',
                'image' => 'images/brands/S.J. & G. Fazal Elahi.jpeg'
            ],
            [
                'name' => 'Genetics Pharmaceuticals',
                'image' => 'images/brands/genetics pharmaceuticals.jpeg'
            ],
            [
                'name' => 'CCL',
                'image' => 'images/brands/CCL.png'
            ],
            [
                'name' => 'global pharmaceuticals',
                'image' => 'images/brands/global pharmaceuticals.jpeg'
            ],
            [
                'name' => 'Horizon Pharmaceuticals',
                'image' => 'images/brands/Horizon Pharmaceuticals.jpeg'
            ]
        ];

        foreach ($brands as $brand) {
            DB::table('brands')->insert([
                'name' => $brand['name'],
                'image' => $brand['image'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
