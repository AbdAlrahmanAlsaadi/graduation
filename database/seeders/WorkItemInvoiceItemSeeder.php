<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\WorkItemInvoice;
use App\Models\WorkItemInvoiceItem;
use Illuminate\Database\Seeder;

class WorkItemInvoiceItemSeeder extends Seeder
{
    public function run(): void
    {
        $materials = Material::all();

        foreach (WorkItemInvoice::all() as $invoice) {

            $selectedMaterials = $materials->random(
                min(3, $materials->count())
            );

            foreach ($selectedMaterials as $material) {

                $quantity = rand(5, 50);

                $unitPrice = rand(10000, 50000);

                WorkItemInvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'material_id' => $material->id,
                    'material_name_snapshot' => $material->name,
                    'quantity' => $quantity,
                    'unit' => $material->unit,
                    'unit_price' => $unitPrice,
                    'total_price' => $quantity * $unitPrice,
                    'notes' => 'عنصر ضمن الفاتورة',
                ]);
            }
        }
    }
}
