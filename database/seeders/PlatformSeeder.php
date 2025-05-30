<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $platforms = [
            ['name' => 'Facebook', 'type' => 'facebook'],
            ['name' => 'Twitter', 'type' => 'twitter'],
            ['name' => 'LinkedIn', 'type' => 'linkedin'],
            ['name' => 'Instagram', 'type' => 'instagram'],
        ];
    
        foreach ($platforms as $platform) {
            Platform::create($platform);
        }
    }
}
