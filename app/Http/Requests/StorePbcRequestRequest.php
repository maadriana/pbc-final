<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePbcRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'project_id' => ['required', 'exists:projects,id'],
            'template_id' => ['nullable', 'exists:pbc_templates,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['nullable', 'date', 'after:today'],
            'header_info' => ['nullable', 'array'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category' => ['nullable', 'string', 'max:255'],
            'items.*.particulars' => ['required', 'string', 'max:1000'],
            'items.*.date_requested' => ['nullable', 'date'],
            'items.*.is_required' => ['boolean'],
            'items.*.remarks' => ['nullable', 'string', 'max:500'],
            'items.*.order_index' => ['integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Please select a client.',
            'client_id.exists' => 'The selected client is invalid.',
            'project_id.required' => 'Please select a project.',
            'project_id.exists' => 'The selected project is invalid.',
            'title.required' => 'The request title is required.',
            'due_date.after' => 'The due date must be a future date.',
            'items.required' => 'At least one request item is required.',
            'items.min' => 'At least one request item is required.',
            'items.*.particulars.required' => 'The particulars field is required for all items.',
        ];
    }
}
