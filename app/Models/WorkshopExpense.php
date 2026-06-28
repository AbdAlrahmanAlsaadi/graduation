<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopExpense extends Model
{
    protected $fillable = [

        'project_id',

        'work_item_id',

        'created_by',

        'amount',

        'description',
    ];

    protected function casts(): array
    {
        return [

            'amount' => 'decimal:2',
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

}
