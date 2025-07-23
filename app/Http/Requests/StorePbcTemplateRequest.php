<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePbcTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'header_info' => ['nullable', 'array'],
            'header_info.engagement_partner' => ['nullable', 'string', 'max:255'],
            'header_info.engagement_manager' => ['nullable', 'string', 'max:255'],
            'header_info.document_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category' => ['nullable', 'string', 'max:255'],
            'items.*.particulars' => ['required', 'string', 'max:1000'],
            'items.*.is_required' => ['boolean'],
            'items.*.order_index' => ['integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The template name is required.',
            'items.required' => 'At least one template item is required.',
            'items.min' => 'At least one template item is required.',
            'items.*.particulars.required' => 'The particulars field is required for all items.',
        ];
    }
}

