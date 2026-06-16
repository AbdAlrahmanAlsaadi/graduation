<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkItemInvoice extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'project_id',
        'work_item_id',
        'supplier_name',
        'invoice_number',
        'invoice_date',
        'invoice_image',
        'total_amount',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function workItem()
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(
            WorkItemInvoiceItem::class,
            'invoice_id'
        );
    }
}
