<?php

namespace Database\Seeders;

use App\Models\Equipment;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        Equipment::factory()
            ->count(fake()->numberBetween(10, 20))
            ->create();
    }
}
