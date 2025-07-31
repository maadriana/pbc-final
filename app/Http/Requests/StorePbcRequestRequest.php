<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePbcRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->canCreatePbcRequests();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic request details
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date|after_or_equal:today',
            'template_id' => 'nullable|exists:pbc_templates,id',
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'required|exists:projects,id',

            // Items validation
            'items' => 'required|array|min:1',
            'items.*.category' => 'required|in:PF,CF',
            'items.*.particulars' => 'required|string|max:500',
            'items.*.assigned_to' => 'nullable|string|max:255',
            'items.*.due_date' => 'nullable|date',
            'items.*.is_required' => 'nullable', // Will be converted to boolean
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Request title is required.',
            'title.max' => 'Request title cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'due_date.after_or_equal' => 'Due date must be today or in the future.',
            'items.required' => 'At least one request item is required.',
            'items.min' => 'At least one request item is required.',
            'items.*.category.required' => 'Category is required for each item.',
            'items.*.category.in' => 'Category must be either PF (Permanent File) or CF (Current File).',
            'items.*.particulars.required' => 'Request description is required for each item.',
            'items.*.particulars.max' => 'Request description cannot exceed 500 characters.',
            'items.*.assigned_to.max' => 'Assigned to field cannot exceed 255 characters.',
            'items.*.due_date.date' => 'Due date must be a valid date.',
            'client_id.required' => 'Client is required.',
            'client_id.exists' => 'Selected client does not exist.',
            'project_id.required' => 'Project is required.',
            'project_id.exists' => 'Selected project does not exist.',
            'template_id.exists' => 'Selected template does not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up the items array and convert checkbox values
        if ($this->has('items')) {
            $items = $this->input('items');

            foreach ($items as $index => $item) {
                // Clean up particulars (trim whitespace)
                if (isset($item['particulars'])) {
                    $items[$index]['particulars'] = trim($item['particulars']);
                }

                // Convert checkbox value to boolean
                $items[$index]['is_required'] = isset($item['is_required']) && $item['is_required'] === '1';

                // Remove empty items (items without particulars)
                if (empty($items[$index]['particulars'])) {
                    unset($items[$index]);
                }
            }

            // Re-index the array after removing empty items
            $items = array_values($items);

            $this->merge(['items' => $items]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation logic
            $this->validateProjectBelongsToClient($validator);
            $this->validateUserAccess($validator);
        });
    }

    /**
     * Validate that the project belongs to the specified client.
     */
    protected function validateProjectBelongsToClient($validator): void
    {
        if ($this->has('client_id') && $this->has('project_id')) {
            $client = \App\Models\Client::find($this->input('client_id'));
            $project = \App\Models\Project::find($this->input('project_id'));

            if ($client && $project && $project->client_id !== $client->id) {
                $validator->errors()->add('project_id', 'The selected project does not belong to the specified client.');
            }
        }
    }

    /**
     * Validate that the user has access to the specified client and project.
     */
    protected function validateUserAccess($validator): void
    {
        $user = auth()->user();

        // Skip validation for system admins
        if ($user->isSystemAdmin()) {
            return;
        }

        if ($this->has('project_id')) {
            $projectId = $this->input('project_id');
            $hasAccess = $user->assignedProjects()->where('projects.id', $projectId)->exists();

            if (!$hasAccess) {
                $validator->errors()->add('project_id', 'You do not have access to this project.');
            }
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'request title',
            'description' => 'description',
            'due_date' => 'due date',
            'client_id' => 'client',
            'project_id' => 'project',
            'template_id' => 'template',
            'items' => 'request items',
            'items.*.category' => 'category',
            'items.*.particulars' => 'request description',
            'items.*.assigned_to' => 'assigned to',
            'items.*.due_date' => 'item due date',
            'items.*.is_required' => 'required status',
        ];
    }
}
