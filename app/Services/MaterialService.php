<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Project;
use App\Models\WorkItem;
use App\Models\WorkItemInvoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MaterialService
{
    /**
     * Get all materials ordered by name.
     */
    public function getAll(): Collection
    {
        return Material::query()->orderBy('name')->get();
    }

    /**
     * Find a material by its ID.
     */
    public function findById(int $id): Material
    {
        $material = Material::query()->find($id);

        if (! $material) {
            throw new RuntimeException('Material not found.', 404);
        }

        return $material;
    }

    /**
     * Create a new material.
     *
     * @param array{name:string, unit:string} $data
     */
    public function create(array $data): Material
    {
        return Material::query()->create($data);
    }

    /**
     * Update an existing material.
     *
     * @param array{name?:string, unit?:string} $data
     */
    public function update(int $id, array $data): Material
    {
        $material = $this->findById($id);
        $material->update($data);
        $material->save();

        return $material->fresh();
    }

    /**
     * Delete a material by ID.
     */
    public function delete(int $id): bool
    {
        $material = $this->findById($id);

        return (bool) $material->delete();
    }

    /**
     * Get materials with attached work items and ordered pivot data.
     */
    public function getMaterialsWithWorkItems(): Collection
    {
        return Material::query()
            ->with(['workItems' => fn ($query) => $query->orderBy('work_item_materials.sort_order')])
            ->orderBy('name')
            ->get();
    }
    public function storeInvoice($request): array
{
$request->validated();


$user = Auth::user();

$project = Project::query()
    ->find($request->project_id);

if (! $project) {
    throw new \Exception(
        'Project not found.',
        404
    );
}

$workItem = WorkItem::query()
    ->find($request->work_item_id);

if (! $workItem) {
    throw new \Exception(
        'Work item not found.',
        404
    );
}

if ($workItem->project_id != $project->id) {
    throw new \Exception(
        'Work item does not belong to this project.',
        422
    );
}

$isCompanyAdmin =
    $user->hasRole('company_admin');

$isProjectManager =
    $user->hasRole('project_manager')
    && $project->project_manager_id == $user->id;

$isAssistant =
    $user->hasRole('assistant')
    && $project->assistant_engineer_id == $user->id;

if (
    ! $isCompanyAdmin
    && ! $isProjectManager
    && ! $isAssistant
) {
    throw new \Exception(
        'You are not allowed to create invoices for this project.',
        403
    );
}

$invoice = DB::transaction(function () use (
    $request,
    $project,
    $workItem,
    $user
) {

    $lastInvoice = WorkItemInvoice::query()
        ->latest('id')
        ->first();

    $nextNumber = $lastInvoice
        ? $lastInvoice->id + 1
        : 1;

    $invoiceNumber =
        'INV-'
        . now()->format('Ymd')
        . '-'
        . str_pad(
            $nextNumber,
            5,
            '0',
            STR_PAD_LEFT
        );

    $totalAmount = 0;

    $invoice = WorkItemInvoice::query()
        ->create([

            'project_id' => $project->id,

            'work_item_id' => $workItem->id,

            'supplier_name' =>
            $request->supplier_name,

            'invoice_number' =>
            $invoiceNumber,

            'invoice_date' =>
            today(),

            'invoice_image' =>
            $request->invoice_image,

            'notes' =>
            $request->notes,

            'total_amount' => 0,

            'created_by' =>
            $user->id,
        ]);

    foreach ($request->items as $item) {

        $material = Material::query()
            ->find($item['material_id']);

        $itemTotal =
            $item['quantity']
            * $item['unit_price'];

        $totalAmount += $itemTotal;

        $invoice->items()->create([

            'material_id' =>
            $material->id,

            'material_name_snapshot' =>
            $material->name,

            'quantity' =>
            $item['quantity'],

            'unit' =>
            $material->unit,

            'unit_price' =>
            $item['unit_price'],

            'total_price' =>
            $itemTotal,

            'notes' =>
            $item['notes'] ?? null,
        ]);
    }

    $invoice->update([
        'total_amount' => $totalAmount,
    ]);

    $invoice = $invoice->fresh([
        'items',
    ]);

    return [

        'id' => $invoice->id,

        'invoice_number' =>
        $invoice->invoice_number,

        'invoice_date' =>
        $invoice->invoice_date,

        'supplier_name' =>
        $invoice->supplier_name,

        'project' => [
            'id' => $project->id,
            'name' => $project->name,
        ],

        'work_item' => [
            'id' => $workItem->id,
            'name' => $workItem->name,
        ],

        'total_amount' =>
        $invoice->total_amount,

        'notes' =>
        $invoice->notes,

        'items' => $invoice->items->map(
            function ($item) {

                return [

                    'id' => $item->id,

                    'material' => [
                        'id' => $item->material_id,
                        'name' => $item->material_name_snapshot,
                    ],

                    'quantity' =>
                    $item->quantity,

                    'unit' =>
                    $item->unit,

                    'unit_price' =>
                    $item->unit_price,

                    'total_price' =>
                    $item->total_price,

                    'notes' =>
                    $item->notes,
                ];
            }
        ),

        'created_by' => [
            'id' => $user->id,
            'name' => $user->name,
        ],

        'created_at' =>
        $invoice->created_at,
    ];
});

return [

    'message' =>
    'Invoice created successfully.',

    'invoice' => $invoice,

    'status' => 201,
];


}
    public function getProjectInvoices($projectId): array
    {
        $project = Project::find($projectId);

        if (! $project) {
            throw new \Exception(
                'Project not found.',
                404
            );
        }

        $invoices = WorkItemInvoice::query()
            ->where('project_id', $projectId)
            ->with([
                'workItem:id,name',
                'creator:id,name',
            ])
            ->latest()
            ->get()
            ->map(function ($invoice) {

                return [

                    'id' => $invoice->id,

                    'invoice_number' =>
                    $invoice->invoice_number,

                    'invoice_date' =>
                    $invoice->invoice_date,

                    'supplier_name' =>
                    $invoice->supplier_name,

                    'work_item' => [
                        'id' => $invoice->workItem->id,
                        'name' => $invoice->workItem->name,
                    ],

                    'total_amount' =>
                    $invoice->total_amount,

                    'created_by' => [
                        'id' => $invoice->creator->id,
                        'name' => $invoice->creator->name,
                    ],

                    'created_at' =>
                    $invoice->created_at,
                ];
            });

        return [

            'message' =>
            'Project invoices fetched successfully.',

            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],

            'invoices' => $invoices,

            'status' => 200,
        ];
    }
    public function deleteInvoice($invoiceId): array
    {
        $invoice = WorkItemInvoice::query()
            ->with([
                'project:id,name',
                'workItem:id,name',
            ])
            ->find($invoiceId);

        if (! $invoice) {
            throw new \Exception(
                'Invoice not found.',
                404
            );
        }

        $invoice->delete();

        return [

            'message' =>
            'Invoice archived successfully.',

            'invoice' => [

                'id' => $invoice->id,

                'invoice_number' =>
                $invoice->invoice_number,

                'project' => [
                    'id' => $invoice->project->id,
                    'name' => $invoice->project->name,
                ],

                'work_item' => [
                    'id' => $invoice->workItem->id,
                    'name' => $invoice->workItem->name,
                ],
            ],

            'status' => 200,
        ];
    }
    public function getArchivedInvoices($projectId): array
{
$project = Project::query()
->find($projectId);

if (! $project) {
    throw new \Exception(
        'Project not found.',
        404
    );
}

$invoices = WorkItemInvoice::onlyTrashed()
    ->where('project_id', $projectId)
    ->with([
        'workItem:id,name',
        'creator:id,name',
    ])
    ->latest('deleted_at')
    ->get()
    ->map(function ($invoice) {

        return [

            'id' => $invoice->id,

            'invoice_number' =>
            $invoice->invoice_number,

            'invoice_date' =>
            $invoice->invoice_date,

            'supplier_name' =>
            $invoice->supplier_name,

            'total_amount' =>
            $invoice->total_amount,

            'work_item' => [
                'id' => $invoice->workItem?->id,
                'name' => $invoice->workItem?->name,
            ],

            'created_by' => [
                'id' => $invoice->creator?->id,
                'name' => $invoice->creator?->name,
            ],

            'deleted_at' =>
            $invoice->deleted_at,
        ];
    });

return [

    'message' =>
    'Archived invoices fetched successfully.',

    'project' => [
        'id' => $project->id,
        'name' => $project->name,
    ],

    'invoices' => $invoices,

    'status' => 200,
];


}
}
