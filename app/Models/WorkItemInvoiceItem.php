<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkItemInvoiceItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'invoice_id',
        'material_id',
        'material_name_snapshot',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(
            WorkItemInvoice::class,
            'invoice_id'
        );
    }

    public function material()
    {
        return $this->belongsTo(
            Material::class
        );
    }
}
