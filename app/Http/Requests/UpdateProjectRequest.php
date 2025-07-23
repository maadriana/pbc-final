<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
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
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'completed', 'on_hold'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The project name is required.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'status.required' => 'Please select a project status.',
            'status.in' => 'The selected status is invalid.',
        ];
    }
}
