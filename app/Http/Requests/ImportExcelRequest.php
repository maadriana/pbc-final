<?php
// File: app/Http/Requests/ImportExcelRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportExcelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->canCreatePbcRequests();
    }

    public function rules(): array
    {
        return [
            'excel_file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240', // 10MB max
            ],
            'project_id' => [
                'required',
                'exists:projects,id',
                function ($attribute, $value, $fail) {
                    // Check if user has access to this project
                    if (!auth()->user()->isSystemAdmin()) {
                        $hasAccess = auth()->user()->assignedProjects()
                            ->where('projects.id', $value)
                            ->exists();

                        if (!$hasAccess) {
                            $fail('You do not have access to this project.');
                        }
                    }
                },
            ],
            'client_id' => [
                'required',
                'exists:clients,id',
                function ($attribute, $value, $fail) {
                    // Check if client is associated with the selected project
                    $projectId = $this->input('project_id');
                    if ($projectId) {
                        $project = \App\Models\Project::find($projectId);
                        if ($project && $project->client_id != $value) {
                            $fail('The selected client is not associated with this project.');
                        }
                    }
                },
            ],
            'import_mode' => [
                'nullable',
                Rule::in(['preview', 'direct']),
            ],
            'overwrite_existing' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'excel_file.required' => 'Please select an Excel file to import.',
            'excel_file.mimes' => 'Only Excel files (.xlsx, .xls) and CSV files are allowed.',
            'excel_file.max' => 'The file size cannot exceed 10MB.',
            'project_id.required' => 'Please select a project for the import.',
            'project_id.exists' => 'The selected project is invalid.',
            'client_id.required' => 'Please select a client for the import.',
            'client_id.exists' => 'The selected client is invalid.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional validation for file content
            if ($this->hasFile('excel_file')) {
                $file = $this->file('excel_file');

                // Check if file is not corrupted
                try {
                    if ($file->getClientOriginalExtension() === 'csv') {
                        // Basic CSV validation
                        $handle = fopen($file->getPathname(), 'r');
                        if (!$handle) {
                            $validator->errors()->add('excel_file', 'Unable to read the CSV file.');
                        } else {
                            fclose($handle);
                        }
                    } else {
                        // Basic Excel validation using PhpSpreadsheet
                        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
                        if (!$reader->canRead($file->getPathname())) {
                            $validator->errors()->add('excel_file', 'The Excel file appears to be corrupted or invalid.');
                        }
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add('excel_file', 'Error reading the file: ' . $e->getMessage());
                }
            }
        });
    }

    /**
     * Get the validated data with defaults
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();

        // Set defaults
        $data['import_mode'] = $data['import_mode'] ?? 'preview';
        $data['overwrite_existing'] = $data['overwrite_existing'] ?? false;

        return $data;
    }

    /**
     * Check if this is a direct import (skip preview)
     */
    public function isDirectImport(): bool
    {
        return $this->input('import_mode') === 'direct';
    }

    /**
     * Check if should overwrite existing data
     */
    public function shouldOverwriteExisting(): bool
    {
        return $this->boolean('overwrite_existing');
    }
}
