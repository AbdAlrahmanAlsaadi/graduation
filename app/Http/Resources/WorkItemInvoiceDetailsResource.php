<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkItemInvoiceDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'supplier_name' => $this->supplier_name,

            'invoice_number' => $this->invoice_number,

            'invoice_date' => $this->invoice_date,

            'invoice_image' => $this->invoice_image,

            'total_amount' => $this->total_amount,

            'notes' => $this->notes,

            'project' => [

                'id' => $this->project?->id,

                'name' => $this->project?->name,
            ],

            'work_item' => [

                'id' => $this->workItem?->id,

                'name' => $this->workItem?->name,
            ],

            'items' => $this->whenLoaded(
                'items',
                fn() => $this->items->map(function ($item) {

                    return [

                        'id' => $item->id,

                        'material_id' => $item->material_id,

                        'material_name' => $item->material_name_snapshot,

                        'quantity' => $item->quantity,

                        'unit' => $item->unit,

                        'unit_price' => $item->unit_price,

                        'total_price' => $item->total_price,

                        'notes' => $item->notes,
                    ];
                })
            ),

            'created_at' => $this->created_at,
        ];
    }
}
