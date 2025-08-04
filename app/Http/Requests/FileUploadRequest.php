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
                'max:307200',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,zip,rar,txt,csv'
            ],
            'pbc_request_item_id' => ['required', 'exists:pbc_request_items,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'The file size cannot exceed 300MB.',
            'file.mimes' => 'Only PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, PNG, JPG, JPEG, ZIP, RAR, TXT, and CSV files are allowed.',
            'pbc_request_item_id.required' => 'Invalid request item.',
            'pbc_request_item_id.exists' => 'The request item does not exist.',
        ];
    }
}
