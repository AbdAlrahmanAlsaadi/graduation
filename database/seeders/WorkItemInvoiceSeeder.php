<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\WorkItem;
use App\Models\WorkItemInvoice;
use Illuminate\Database\Seeder;

class WorkItemInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();

        foreach ($projects as $project) {

            $workItem = WorkItem::where(
                'project_id',
                $project->id
            )->first();

            if (! $workItem) {
                continue;
            }

            WorkItemInvoice::create([
                'project_id'      => $project->id,
                'work_item_id'    => $workItem->id,
                'supplier_name'   => 'شركة البناء الحديثة',
                'invoice_number'  => 'INV-' . rand(1000, 9999),
                'invoice_date'    => now()->subDays(rand(1, 20)),
                'invoice_image'   => null,
                'total_amount'    => 2500000,
                'notes'           => 'فاتورة مواد أولية للبند',
                'created_by'      => 1,
            ]);
        }
    }
}
