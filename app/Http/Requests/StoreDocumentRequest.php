<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'custom_name' => ['required', 'string', 'max:255'],

            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'The project_id field is required.',
            'project_id.exists' => 'The selected project does not exist.',
            'title.required' => 'The title field is required.',
            'category.required' => 'The category field is required.',
            'file.required' => 'The file field is required.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'Allowed file types are: pdf, doc, docx, xlsx, xls, png, jpg, jpeg.',
        ];
    }
}
