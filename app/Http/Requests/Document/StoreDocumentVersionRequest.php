<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg'],
            'custom_name' => ['required', 'string', 'max:255'],

        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'The file field is required.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'Allowed file types are: pdf, doc, docx, xlsx, xls, png, jpg, jpeg.',
        ];
    }
}
