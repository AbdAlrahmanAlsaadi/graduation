<?php

namespace Database\Seeders;

use App\Models\Equipment;
use Illuminate\Database\Seeder;

class AvailableEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        Equipment::factory()
            ->count(10)
            ->state([
                'status' => 'Available',
            ])
            ->create();
    }
}
