<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PbcRequest;
use App\Models\PbcRequestItem;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportController extends Controller
{
    /**
     * Show global import form
     */
    public function showImportForm()
    {
        if (!auth()->user()->canCreatePbcRequests()) {
            abort(403, 'You do not have permission to import PBC requests.');
        }

        $projects = $this->getAccessibleProjects();
        return view('admin.pbc-requests.import', compact('projects'));
    }

    /**
     * Show project-specific import form
     */
    public function showProjectImportForm(Client $client, Project $project)
    {
        if (!auth()->user()->canCreatePbcRequests()) {
            abort(403, 'You do not have permission to import PBC requests.');
        }

        // Security check
        if (!$this->canAccessClient($client) || !$this->canAccessProject($project->id)) {
            abort(403, 'You do not have permission to access this project.');
        }

        // Verify project belongs to client
        if ($project->client_id !== $client->id) {
            abort(404, 'Project not found for this client.');
        }

        return view('admin.clients.projects.pbc-requests.import', compact('client', 'project'));
    }

    /**
     * Preview import data (global)
     */
    public function preview(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date|after_or_equal:today',
        ]);

        try {
            // Security check
            if (!$this->canAccessProject($request->project_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this project.'
                ], 403);
            }

            $file = $request->file('excel_file');
            $data = $this->processImportFile($file);

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid data found in the file.'
                ]);
            }

            // Store preview data in session
            session(['import_preview_data' => [
                'project_id' => $request->project_id,
                'title' => $request->title,
                'description' => $request->description,
                'due_date' => $request->due_date,
                'items' => $data,
                'file_name' => $file->getClientOriginalName(),
            ]]);

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => count($data)
            ]);

        } catch (\Exception $e) {
            Log::error('Import preview error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process file: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Preview import data (project-specific)
     */
    public function projectPreview(Request $request, Client $client, Project $project)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date|after_or_equal:today',
        ]);

        // Security check
        if (!$this->canAccessClient($client) || !$this->canAccessProject($project->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this project.'
            ], 403);
        }

        try {
            $file = $request->file('excel_file');
            $data = $this->processImportFile($file);

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid data found in the file.'
                ]);
            }

            // Store preview data in session
            session(['import_preview_data' => [
                'project_id' => $project->id,
                'client_id' => $client->id,
                'title' => $request->title,
                'description' => $request->description,
                'due_date' => $request->due_date,
                'items' => $data,
                'file_name' => $file->getClientOriginalName(),
            ]]);

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => count($data)
            ]);

        } catch (\Exception $e) {
            Log::error('Project import preview error', [
                'error' => $e->getMessage(),
                'client_id' => $client->id,
                'project_id' => $project->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process file: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Execute import (global)
     */
    public function import(Request $request)
    {
        if (!session()->has('import_preview_data')) {
            return response()->json([
                'success' => false,
                'message' => 'No preview data found. Please preview first.'
            ]);
        }

        $previewData = session('import_preview_data');

        try {
            DB::transaction(function () use ($previewData) {
                $project = Project::findOrFail($previewData['project_id']);

                // Create PBC request
                $pbcRequest = PbcRequest::create([
                    'client_id' => $project->client_id,
                    'project_id' => $project->id,
                    'title' => $previewData['title'],
                    'description' => $previewData['description'],
                    'due_date' => $previewData['due_date'],
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]);

                // Create request items
                foreach ($previewData['items'] as $index => $item) {
                    if (!empty(trim($item['particulars']))) {
                        PbcRequestItem::create([
                            'pbc_request_id' => $pbcRequest->id,
                            'category' => $item['category'],
                            'particulars' => trim($item['particulars']),
                            'assigned_to' => $item['assigned_to'] ?? null,
                            'date_requested' => now()->toDateString(),
                            'due_date' => $item['due_date'],
                            'is_required' => $item['is_required'] ?? true,
                            'status' => 'pending',
                            'order_index' => $index,
                            'requestor' => auth()->user()->name,
                        ]);
                    }
                }

                Log::info('PBC Request imported successfully', [
                    'pbc_request_id' => $pbcRequest->id,
                    'items_count' => count($previewData['items']),
                    'file_name' => $previewData['file_name'],
                    'created_by' => auth()->id()
                ]);
            });

            // Clear session data
            session()->forget('import_preview_data');

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Import execution error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Execute project import
     */
    public function projectImport(Request $request, Client $client, Project $project)
    {
        if (!session()->has('import_preview_data')) {
            return response()->json([
                'success' => false,
                'message' => 'No preview data found. Please preview first.'
            ]);
        }

        $previewData = session('import_preview_data');

        // Security check
        if (!$this->canAccessClient($client) || !$this->canAccessProject($project->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this project.'
            ], 403);
        }

        try {
            DB::transaction(function () use ($previewData, $client, $project) {
                // Create PBC request
                $pbcRequest = PbcRequest::create([
                    'client_id' => $client->id,
                    'project_id' => $project->id,
                    'title' => $previewData['title'],
                    'description' => $previewData['description'],
                    'due_date' => $previewData['due_date'],
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]);

                // Create request items
                foreach ($previewData['items'] as $index => $item) {
                    if (!empty(trim($item['particulars']))) {
                        PbcRequestItem::create([
                            'pbc_request_id' => $pbcRequest->id,
                            'category' => $item['category'],
                            'particulars' => trim($item['particulars']),
                            'assigned_to' => $item['assigned_to'] ?? null,
                            'date_requested' => now()->toDateString(),
                            'due_date' => $item['due_date'],
                            'is_required' => $item['is_required'] ?? true,
                            'status' => 'pending',
                            'order_index' => $index,
                            'requestor' => auth()->user()->name,
                        ]);
                    }
                }

                Log::info('Project PBC Request imported successfully', [
                    'pbc_request_id' => $pbcRequest->id,
                    'client_id' => $client->id,
                    'project_id' => $project->id,
                    'items_count' => count($previewData['items']),
                    'file_name' => $previewData['file_name'],
                    'created_by' => auth()->id()
                ]);
            });

            // Clear session data
            session()->forget('import_preview_data');

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Project import execution error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'client_id' => $client->id,
                'project_id' => $project->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'A1' => 'Category',
            'B1' => 'Request Description',
            'C1' => 'Assigned To',
            'D1' => 'Due Date',
            'E1' => 'Required'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        // Add sample data
        $sampleData = [
            ['CF', 'Bank confirmation letters', 'Client Finance Team', '2024-12-31', 'TRUE'],
            ['PF', 'Trial balance as of year end', 'Accounting Manager', '2024-12-30', 'TRUE'],
            ['CF', 'Inventory count sheets', 'Warehouse Manager', '2024-12-28', 'FALSE'],
        ];

        $row = 2;
        foreach ($sampleData as $data) {
            $col = 'A';
            foreach ($data as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create writer and save
        $writer = new Xlsx($spreadsheet);
        $filename = 'pbc_import_template.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'pbc_template');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend();
    }

    /**
     * Download project-specific template
     */
    public function downloadProjectTemplate(Client $client, Project $project)
{
    // Security check
    if (!$this->canAccessClient($client) || !$this->canAccessProject($project->id)) {
        abort(403, 'You do not have permission to access this project.');
    }

    // Verify project belongs to client
    if ($project->client_id !== $client->id) {
        abort(404, 'Project not found for this client.');
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set title
    $sheet->setCellValue('A1', 'PBC Import Template for: ' . $project->engagement_name);
    $sheet->mergeCells('A1:E1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    // Set headers
    $headers = [
        'A3' => 'Category',
        'B3' => 'Request Description',
        'C3' => 'Assigned To',
        'D3' => 'Due Date',
        'E3' => 'Required'
    ];

    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getStyle($cell)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E2E2');
    }

    // Add instructions
    $sheet->setCellValue('A2', 'Instructions: Fill in the data below. Category should be CF (Confirmed by Firm) or PF (Provided by Firm)');
    $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
    $sheet->mergeCells('A2:E2');

    // Add sample data
    $sampleData = [
        ['CF', 'Bank confirmation letters for all accounts', 'Finance Manager', '2024-12-31', 'TRUE'],
        ['PF', 'General ledger trial balance as of year end', 'Accounting Team', '2024-12-30', 'TRUE'],
        ['CF', 'Physical inventory count sheets', 'Warehouse Supervisor', '2024-12-28', 'FALSE'],
        ['PF', 'Accounts receivable aging report', 'AR Clerk', '', 'TRUE'],
        ['CF', 'Fixed assets listing with depreciation', 'Asset Manager', '2024-12-29', 'TRUE'],
    ];

    $row = 4;
    foreach ($sampleData as $data) {
        $col = 'A';
        foreach ($data as $value) {
            $sheet->setCellValue($col . $row, $value);
            // Add light gray background to sample data
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF8F8F8');
            $col++;
        }
        $row++;
    }

    // Add data validation for Category column
    $categoryValidation = $sheet->getCell('A4')->getDataValidation();
    $categoryValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
    $categoryValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
    $categoryValidation->setAllowBlank(false);
    $categoryValidation->setShowInputMessage(true);
    $categoryValidation->setShowErrorMessage(true);
    $categoryValidation->setShowDropDown(true);
    $categoryValidation->setErrorTitle('Input error');
    $categoryValidation->setError('Value must be CF or PF');
    $categoryValidation->setPromptTitle('Category');
    $categoryValidation->setPrompt('Choose CF (Confirmed by Firm) or PF (Provided by Firm)');
    $categoryValidation->setFormula1('"CF,PF"');

    // Apply validation to a range
    $sheet->setDataValidation('A4:A100', clone $categoryValidation);

    // Auto-size columns
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Add borders to header
    $styleArray = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
    ];
    $sheet->getStyle('A3:E8')->applyFromArray($styleArray);

    // Create writer and save
    $writer = new Xlsx($spreadsheet);
    $filename = 'pbc_import_template_' . $project->job_id . '.xlsx';
    $tempFile = tempnam(sys_get_temp_dir(), 'pbc_template');
    $writer->save($tempFile);

    return response()->download($tempFile, $filename)->deleteFileAfterSend();
}

    /**
     * Get import statistics
     */
    public function getImportStats()
    {
        return response()->json([
            'can_import' => auth()->user()->canCreatePbcRequests(),
            'total_imported_today' => PbcRequest::whereDate('created_at', today())->count(),
            'pending_imports' => session()->has('import_preview_data'),
        ]);
    }

    /**
     * Process import file
     */
    private function processImportFile($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $data = [];

        try {
            if (in_array($extension, ['xlsx', 'xls'])) {
                $spreadsheet = IOFactory::load($file->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                // Find header row
                $headerRow = 0;
                for ($i = 0; $i < min(5, count($rows)); $i++) {
                    if (isset($rows[$i]) && is_array($rows[$i])) {
                        $firstRow = array_map('strtolower', array_map('trim', $rows[$i]));
                        if (in_array('category', $firstRow) ||
                            in_array('particulars', $firstRow) ||
                            in_array('request description', $firstRow)) {
                            $headerRow = $i + 1; // Start from next row
                            break;
                        }
                    }
                }

                // Process data rows
                for ($i = $headerRow; $i < count($rows); $i++) {
                    $row = $rows[$i];

                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Get particulars from column B or A if B is empty
                    $particulars = trim($row[1] ?? $row[0] ?? '');
                    if (empty($particulars)) {
                        continue;
                    }

                    $data[] = [
                        'category' => $this->mapCategory($row[0] ?? ''),
                        'particulars' => $particulars,
                        'assigned_to' => trim($row[2] ?? ''),
                        'due_date' => $this->parseDate($row[3] ?? ''),
                        'is_required' => $this->parseBoolean($row[4] ?? true),
                    ];
                }

            } elseif ($extension === 'csv') {
                $data = $this->processCsvFile($file);
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('File processing error', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
            ]);

            throw new \Exception('Failed to process file: ' . $e->getMessage());
        }
    }

    /**
     * Process CSV file
     */
    private function processCsvFile($file)
    {
        $data = [];
        $csvData = array_map('str_getcsv', file($file->getPathname()));

        // Skip header row if exists
        $startRow = 0;
        if (isset($csvData[0]) && is_array($csvData[0])) {
            $firstRow = array_map('strtolower', array_map('trim', $csvData[0]));
            if (in_array('category', $firstRow) ||
                in_array('particulars', $firstRow) ||
                in_array('request description', $firstRow)) {
                $startRow = 1;
            }
        }

        for ($i = $startRow; $i < count($csvData); $i++) {
            $row = $csvData[$i];

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $particulars = trim($row[1] ?? $row[0] ?? '');
            if (empty($particulars)) {
                continue;
            }

            $data[] = [
                'category' => $this->mapCategory($row[0] ?? ''),
                'particulars' => $particulars,
                'assigned_to' => trim($row[2] ?? ''),
                'due_date' => $this->parseDate($row[3] ?? ''),
                'is_required' => $this->parseBoolean($row[4] ?? true),
            ];
        }

        return $data;
    }

    /**
     * Map category values
     */
    private function mapCategory($value)
    {
        $value = strtoupper(trim($value));

        switch ($value) {
            case 'PF':
            case 'PROVIDED BY FIRM':
            case 'PROVIDED':
            case 'P':
                return 'PF';
            case 'CF':
            case 'CONFIRMED BY FIRM':
            case 'CONFIRMED':
            case 'C':
                return 'CF';
            default:
                return 'PF'; // Default to PF if unknown
        }
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // If it's already a DateTime object (from Excel)
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d');
        }

        // If it's a numeric Excel date
        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // Not a valid Excel date, try parsing as string
            }
        }

        // Try parsing as string
        try {
            $date = new \DateTime($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse boolean values
     */
    private function parseBoolean($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtoupper(trim($value));
        return in_array($value, ['TRUE', 'YES', '1', 'REQUIRED', 'Y']);
    }

    /**
     * Check if current user can access the given project
     */
    private function canAccessProject($projectId)
    {
        if (auth()->user()->isSystemAdmin()) {
            return true;
        }

        return auth()->user()->assignedProjects()->where('projects.id', $projectId)->exists();
    }

    /**
     * Check if current user can access the given client
     */
    private function canAccessClient(Client $client)
    {
        if (auth()->user()->isSystemAdmin()) {
            return true;
        }

        $userProjectIds = auth()->user()->assignedProjects()->pluck('projects.id');
        $clientProjectIds = $client->projects()->pluck('id');

        return $userProjectIds->intersect($clientProjectIds)->isNotEmpty();
    }

    /**
     * Get projects accessible to current user
     */
    private function getAccessibleProjects()
    {
        $query = Project::with('client')->where('status', 'active');

        if (!auth()->user()->isSystemAdmin()) {
            $assignedProjectIds = auth()->user()->assignedProjects()->pluck('projects.id');
            $query->whereIn('id', $assignedProjectIds);
        }

        return $query->orderBy('name')->get();
    }
}
