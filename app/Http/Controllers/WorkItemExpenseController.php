<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseFilterRequest;
use App\Http\Requests\StoreWorkshopExpenseRequest;
use App\Http\Responses\Response;
use App\Models\Project;
use App\Models\WorkItem;
use App\Services\WorkItem\WorkItemExpenseService;
use Throwable;

class WorkItemExpenseController extends Controller
{
    public function __construct(
        protected WorkItemExpenseService $expenseService
    ) {}

    public function store(
        StoreWorkshopExpenseRequest $request,
        Project $project,
        WorkItem $workItem
    ) {
        try {

            $expense = $this->expenseService->store(
                $project,
                $workItem,
                $request->validated()
            );
            $expense->load([
                'project:id,name',
                'workItem:id,name',
                'creator:id,name',
            ]);
            return Response::success(
                'Expense added successfully.',
                [

                    'id' => $expense->id,

                    'project' => [
                        'id' => $expense->project->id,
                        'name' => $expense->project->name,
                    ],

                    'work_item' => [
                        'id' => $expense->workItem->id,
                        'name' => $expense->workItem->name,
                    ],

                    'created_by' => [
                        'id' => $expense->creator->id,
                        'name' => $expense->creator->name,
                    ],

                    'amount' => $expense->amount,

                    'description' => $expense->description,

                    'created_at' => $expense->created_at,

                    'updated_at' => $expense->updated_at,
                ]
            );
        } catch (Throwable $e) {

            return Response::error(
                $e->getMessage(),
                $e->getCode() ?: 500
            );
        }
    }

    public function index(
        ExpenseFilterRequest $request,
        Project $project,
        WorkItem $workItem
    ) {
        try {

            $data = $this->expenseService->getExpenses(
                $project,
                $workItem,
                $request->validated()
            );

            return Response::success(

                'Expenses fetched successfully.',

                [

                    'project' => [

                        'id' => $project->id,

                        'name' => $project->name,
                    ],

                    'work_item' => [

                        'id' => $workItem->id,

                        'name' => $workItem->name,
                    ],

                    'from' => $request->from,

                    'to' => $request->to,

                    'total_amount' => $data['total_amount'],

                    'expenses' => $data['expenses']->map(function ($expense) {

                        return [

                            'id' => $expense->id,

                            'amount' => $expense->amount,

                            'description' => $expense->description,

                            'created_by' => [

                                'id' => $expense->creator?->id,

                                'name' => $expense->creator?->name,
                            ],

                            'created_at' => $expense->created_at,
                        ];
                    }),
                ]
            );
        } catch (Throwable $e) {

            return Response::error(
                $e->getMessage(),
                $e->getCode() ?: 500
            );
        }
    }
}
