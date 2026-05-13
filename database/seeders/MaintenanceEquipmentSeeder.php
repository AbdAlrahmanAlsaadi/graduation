<?php

namespace Database\Seeders;

use App\Models\Equipment;
use Illuminate\Database\Seeder;

class MaintenanceEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        Equipment::factory()
            ->count(5)
            ->state([
                'status' => 'Maintenance',
            ])
            ->create();
    }
}
