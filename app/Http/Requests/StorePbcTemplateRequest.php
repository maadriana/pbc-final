<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\PbcTemplateItem;

class StorePbcTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Template basic info
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],

            // Header information
            'header_info' => ['nullable', 'array'],
            'header_info.engagement_partner' => ['nullable', 'string', 'max:255'],
            'header_info.engagement_manager' => ['nullable', 'string', 'max:255'],
            'header_info.client_name' => ['nullable', 'string', 'max:255'],
            'header_info.audit_period' => ['nullable', 'string', 'max:255'],
            'header_info.document_date' => ['nullable', 'date'],
            'header_info.due_date' => ['nullable', 'date'],

            // Template items validation
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array'],

            // UPDATED: Restrict categories to CF/PF only using constants
            'items.*.category' => [
                'nullable',
                Rule::in([
                    PbcTemplateItem::CATEGORY_CURRENT_FILE,
                    PbcTemplateItem::CATEGORY_PERMANENT_FILE
                ])
            ],
            'items.*.particulars' => ['required', 'string', 'max:1000'],
            'items.*.is_required' => ['boolean'],
            'items.*.order_index' => ['integer', 'min:0'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            // Template validation messages
            'name.required' => 'The template name is required.',
            'name.max' => 'The template name may not be greater than 255 characters.',
            'description.max' => 'The description may not be greater than 1000 characters.',

            // Header info validation messages
            'header_info.engagement_partner.max' => 'The engagement partner name is too long.',
            'header_info.engagement_manager.max' => 'The engagement manager name is too long.',
            'header_info.client_name.max' => 'The client name is too long.',
            'header_info.audit_period.max' => 'The audit period is too long.',
            'header_info.document_date.date' => 'The document date must be a valid date.',
            'header_info.due_date.date' => 'The due date must be a valid date.',

            // Items validation messages
            'items.required' => 'At least one template item is required.',
            'items.min' => 'At least one template item is required.',
            'items.*.required' => 'All template items must be properly filled.',

            // Category validation messages
            'items.*.category.in' => 'Category must be either CF (Current File) or PF (Permanent File).',

            // Particulars validation messages
            'items.*.particulars.required' => 'The particulars field is required for all items.',
            'items.*.particulars.max' => 'The particulars may not be greater than 1000 characters.',

            // Other item validation messages
            'items.*.is_required.boolean' => 'The required field must be true or false.',
            'items.*.order_index.integer' => 'The order index must be a number.',
            'items.*.order_index.min' => 'The order index must be at least 0.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'template name',
            'description' => 'template description',
            'header_info.engagement_partner' => 'engagement partner',
            'header_info.engagement_manager' => 'engagement manager',
            'header_info.client_name' => 'client name',
            'header_info.audit_period' => 'audit period',
            'header_info.document_date' => 'document date',
            'header_info.due_date' => 'due date',
            'items.*.category' => 'category',
            'items.*.particulars' => 'particulars',
            'items.*.is_required' => 'required status',
            'items.*.order_index' => 'order index',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }

        // Clean up items array - remove empty items
        if ($this->has('items')) {
            $items = collect($this->items)->filter(function ($item) {
                return !empty($item['particulars']);
            })->values()->toArray();

            $this->merge(['items' => $items]);
        }

        // Ensure order_index is set for all items
        if ($this->has('items')) {
            $items = collect($this->items)->map(function ($item, $index) {
                if (!isset($item['order_index'])) {
                    $item['order_index'] = $index;
                }
                return $item;
            })->toArray();

            $this->merge(['items' => $items]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: ensure at least one required item
            if ($this->has('items')) {
                $hasRequiredItem = collect($this->items)->contains('is_required', true);

                if (!$hasRequiredItem) {
                    $validator->errors()->add('items', 'At least one item must be marked as required.');
                }
            }

            // Custom validation: check for duplicate particulars
            if ($this->has('items')) {
                $particulars = collect($this->items)->pluck('particulars')->filter();
                $duplicates = $particulars->duplicates();

                if ($duplicates->isNotEmpty()) {
                    $validator->errors()->add('items', 'Duplicate particulars are not allowed.');
                }
            }

            // Custom validation: validate category distribution
            if ($this->has('items')) {
                $categories = collect($this->items)->pluck('category')->filter();
                $cfCount = $categories->filter(fn($cat) => $cat === PbcTemplateItem::CATEGORY_CURRENT_FILE)->count();
                $pfCount = $categories->filter(fn($cat) => $cat === PbcTemplateItem::CATEGORY_PERMANENT_FILE)->count();

                // Warn if template is too unbalanced (optional business rule)
                if ($cfCount === 0 && $pfCount > 0) {
                    // All PF, no CF - might want to warn
                } elseif ($pfCount === 0 && $cfCount > 0) {
                    // All CF, no PF - might want to warn
                }
            }
        });
    }

    /**
     * Get validated data with processed items
     */
    public function getProcessedData(): array
    {
        $validated = $this->validated();

        // Process items to ensure proper formatting
        if (isset($validated['items'])) {
            $validated['items'] = collect($validated['items'])->map(function ($item, $index) {
                return [
                    'category' => $item['category'] ?? null,
                    'particulars' => trim($item['particulars']),
                    'is_required' => $item['is_required'] ?? true,
                    'order_index' => $item['order_index'] ?? $index,
                ];
            })->toArray();
        }

        return $validated;
    }

    /**
     * Get category options for form
     */
    public static function getCategoryOptions(): array
    {
        return PbcTemplateItem::getCategoryOptions();
    }

    /**
     * Get header info defaults
     */
    public static function getHeaderInfoDefaults(): array
    {
        return [
            'engagement_partner' => '',
            'engagement_manager' => '',
            'client_name' => '',
            'audit_period' => '',
            'document_date' => null,
            'due_date' => null,
        ];
    }
}
