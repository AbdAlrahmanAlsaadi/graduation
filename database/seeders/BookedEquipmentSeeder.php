<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Equipment;
use App\Models\WorkItem;
use App\Models\EquipmentBooking;
use Illuminate\Database\Seeder;

class BookedEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $workItems = WorkItem::query()->get();

        if ($workItems->isEmpty()) {
            return;
        }

        $user = User::query()->find(3);

        if (! $user) {
            return;
        }

        Equipment::factory()
            ->count(5)
            ->state([
                'status' => 'Booked',
            ])
            ->create()

            ->each(function ($equipment) use ($workItems, $user) {

                $workItem = $workItems->random();

                EquipmentBooking::query()->create([

                    'equipment_id' => $equipment->id,

                    'work_item_id' => $workItem->id,

                    'booked_by' => $user->id,

                    'start_date' => now()->subDays(rand(1, 5)),


                    'status' => 'active',

                    'notes' => fake()->sentence(),
                ]);
            });
    }
}
