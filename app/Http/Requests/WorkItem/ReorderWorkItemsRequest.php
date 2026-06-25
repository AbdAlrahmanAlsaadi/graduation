<?php

namespace App\Http\Requests\WorkItem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderWorkItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');
        $projectId = $project?->id;
        $existsRule = Rule::exists('work_items', 'id');

        if ($projectId) {
            $existsRule = $existsRule->where(function ($query) use ($projectId) {
                $query->where('project_id', $projectId);
            });
        }

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'required',
                'integer',
                'distinct',
                $existsRule,
            ],
            'items.*.sort_order' => ['required', 'integer', 'min:1'],
        ];
    }
}
