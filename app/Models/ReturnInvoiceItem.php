<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnInvoiceItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'return_invoice_id',
        'material_id',
        'material_name_snapshot',
        'item_type',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
        'reason',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function returnInvoice(): BelongsTo
    {
        return $this->belongsTo(ReturnInvoice::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
