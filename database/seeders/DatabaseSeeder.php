<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(ProjectsSeeder::class);
        $this->call(SpacesSeeder::class);
        $this->call(WorkItemsSeeder::class);
        $this->call(AvailableEquipmentSeeder::class);
        $this->call(MaintenanceEquipmentSeeder::class);
        $this->call(BookedEquipmentSeeder::class);
        $this->call(MaterialsSeeder::class);
        $this->call(WorkItemMaterialsSeeder::class);
        $this->call(WorkItemInvoiceSeeder::class);
        $this->call(WorkItemInvoiceItemSeeder::class);
        $this->call(NotificationSeeder::class);
        $this->call(ProjectImagesAndAiSeeder::class);
    }
}
