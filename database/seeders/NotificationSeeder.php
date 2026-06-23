<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        $project = Project::first();

        $workItem = WorkItem::first();

        foreach ($users as $user) {

            Notification::create([

                'user_id' => $user->id,

                'project_id' => $project?->id,

                'project_work_item_id' => $workItem?->id,

                'type' => 'notification',

                'title' => 'إشعار',

                'body' => 'إشعار',

                'is_read' => false,

                'data' => [
                    'project_id' => $project?->id,
                    'work_item_id' => $workItem?->id,
                ],
            ]);

            Notification::create([

                'user_id' => $user->id,

                'project_id' => $project?->id,

                'project_work_item_id' => $workItem?->id,

                'type' => 'notification',

                'title' => 'إشعار',

                'body' => 'إشعار',

                'is_read' => true,

                'read_at' => now(),

                'data' => [
                    'project_id' => $project?->id,
                    'work_item_id' => $workItem?->id,
                ],
            ]);
        }
    }
}
