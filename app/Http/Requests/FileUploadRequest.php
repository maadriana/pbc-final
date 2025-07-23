<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:51200', // 50MB
                'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip,txt'
            ],
            'pbc_request_item_id' => ['required', 'exists:pbc_request_items,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'The file size cannot exceed 50MB.',
            'file.mimes' => 'Only PDF, DOC, DOCX, XLS, XLSX, PNG, JPG, JPEG, ZIP, and TXT files are allowed.',
            'pbc_request_item_id.required' => 'Invalid request item.',
            'pbc_request_item_id.exists' => 'The request item does not exist.',
        ];
    }
}
