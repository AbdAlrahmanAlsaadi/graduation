<?php

namespace App\Services\WorkItem;

use App\Models\Project;
use App\Models\WorkItem;
use App\Models\WorkshopExpense;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class WorkItemExpenseService
{
    public function store(
        Project $project,
        WorkItem $workItem,
        array $data
    ): WorkshopExpense {

        if ($workItem->project_id != $project->id) {

            throw new RuntimeException(
                'Work item does not belong to this project.',
                404
            );
        }

        return WorkshopExpense::create([

            'project_id' => $project->id,

            'work_item_id' => $workItem->id,

            'created_by' => Auth::id(),

            'amount' => $data['amount'],

            'description' => $data['description'],
        ]);
    }

    public function getExpenses(
        Project $project,
        WorkItem $workItem,
        array $filters
    ): array {

        if ($workItem->project_id != $project->id) {

            throw new RuntimeException(
                'Work item does not belong to this project.',
                404
            );
        }

        $expenses = WorkshopExpense::query()

            ->with([
                'creator:id,name',
            ])

            ->where('project_id', $project->id)

            ->where('work_item_id', $workItem->id)

            ->whereDate('created_at', '>=', $filters['from'])

            ->whereDate('created_at', '<=', $filters['to'])

            ->latest()

            ->get();

        return [

            'total_amount' => $expenses->sum('amount'),

            'expenses' => $expenses,
        ];
    }

    
}
