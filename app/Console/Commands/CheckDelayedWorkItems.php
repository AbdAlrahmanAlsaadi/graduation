<?php

namespace App\Console\Commands;

use App\Models\DurationExtensionRequest;
use App\Models\WorkItem;
use App\Services\Notification\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CheckDelayedWorkItems extends Command
{
    protected $signature = 'work-items:check-delayed';

    protected $description = 'Check for delayed work items and notify assistants';

    public function handle(NotificationService $notificationService): int
    {
        $delayedItems = WorkItem::query()
            ->where('status', WorkItem::STATUS_ONGOING)
            ->whereNotNull('started_at')
            ->whereNotNull('duration_days')
            ->whereRaw('DATE_ADD(started_at, INTERVAL duration_days DAY) < NOW()')
            ->whereDoesntHave('durationExtensionRequests', function ($query) {
                $query->where('status', DurationExtensionRequest::STATUS_PENDING);
            })
            ->with('project')
            ->get();

        $notified = 0;

        foreach ($delayedItems as $item) {
            $project = $item->project;

            if (!$project) {
                continue;
            }

            $assistant = $project->assistantEngineer;
            Log::info("ok");

            if (!$assistant) {
                $this->warn("No assistant assigned to project #{$project->id} ({$project->name})");
                continue;
            }

            try {
                $notificationService->send($assistant, [
                    'type'                  => 'work_item_delayed',
                    'title'                 => 'تأخر بند العمل',
                    'body'                  => "بند العمل \"{$item->name}\" في المشروع \"{$project->name}\" متأخر. يرجى تقديم طلب لتمديد مدة التنفيذ.",
                    'project_id'            => $project->id,
                    'project_work_item_id'  => $item->id,
                    'data'                  => [
                        'work_item_id' => $item->id,
                        'project_id'   => $project->id,
                    ],
                ]);

                $notified++;
            } catch (\Throwable $e) {
                $this->error("Failed to notify for work item #{$item->id}: {$e->getMessage()}");
                logger()->error('CheckDelayedWorkItems notification failed', [
                    'work_item_id' => $item->id,
                    'error'        => $e->getMessage(),
                ]);
            }
        }

        $this->info("Checked {$delayedItems->count()} delayed work items. Notified {$notified} assistants.");

        return self::SUCCESS;
    }
}
