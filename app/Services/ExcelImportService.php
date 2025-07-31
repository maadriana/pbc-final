<?php
// File: app/Services/ExcelImportService.php

namespace App\Services;

use App\Models\Project;
use App\Models\Client;
use App\Models\User;
use App\Models\PbcRequest;
use App\Models\PbcRequestItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class ExcelImportService
{
    /**
     * Parse Excel file and extract PBC request data
     */
    public function parseExcelFile(UploadedFile $file, int $projectId, int $clientId): array
    {
        $project = Project::with(['engagementPartner', 'manager', 'client.user'])->findOrFail($projectId);
        $client = Client::with('user')->findOrFail($clientId);

        // Load the Excel file
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Expected columns
        $expectedHeaders = [
            'Category',           // CF or PF
            'Request Description', // Particulars
            'Requestor',          // ACTUAL USER (name or email)
            'Date Requested',     // When requested
            'Assigned to',        // ACTUAL CLIENT USER (name or email)
            'Status'              // Pending, Uploaded, etc.
        ];

        // Find header row
        $headerRowIndex = $this->findHeaderRow($rows, $expectedHeaders);

        if ($headerRowIndex === -1) {
            throw new \Exception('Invalid Excel format. Expected headers: ' . implode(', ', $expectedHeaders));
        }

        $headers = $rows[$headerRowIndex];
        $dataRows = array_slice($rows, $headerRowIndex + 1);

        // Get available users for lookup
        $availableUsers = $this->getAvailableUsers($project);
        $clientUsers = $this->getClientUsers($client);

        // Parse data rows
        $parsedRequests = [];
        $currentRequestTitle = null;
        $currentRequestItems = [];
        $currentRequestor = null;
        $requestCounter = 1;
        $errors = [];

        foreach ($dataRows as $rowIndex => $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                // If we have accumulated items, save the current request
                if (!empty($currentRequestItems)) {
                    $parsedRequests[] = $this->createRequestFromItems(
                        $currentRequestTitle ?: "Imported Request {$requestCounter}",
                        $currentRequestItems,
                        $project,
                        $client,
                        $currentRequestor
                    );
                    $currentRequestItems = [];
                    $requestCounter++;
                }
                continue;
            }

            // Map row data to expected structure
            $rowData = $this->mapRowToData($headers, $row, $expectedHeaders);

            // Validate and resolve users
            $validationResult = $this->validateAndResolveUsers(
                $rowData,
                $availableUsers,
                $clientUsers,
                $rowIndex + $headerRowIndex + 2 // Actual Excel row number
            );

            if (!empty($validationResult['errors'])) {
                $errors = array_merge($errors, $validationResult['errors']);
            }

            // If this looks like a new request (different requestor)
            if ($this->isNewRequestRow($rowData, $currentRequestItems, $currentRequestor)) {
                // Save previous request if exists
                if (!empty($currentRequestItems)) {
                    $parsedRequests[] = $this->createRequestFromItems(
                        $currentRequestTitle ?: "Imported Request {$requestCounter}",
                        $currentRequestItems,
                        $project,
                        $client,
                        $currentRequestor
                    );
                    $requestCounter++;
                }

                // Start new request
                $currentRequestor = $validationResult['requestor_user'];
                $currentRequestTitle = $this->generateRequestTitle($rowData, $project, $currentRequestor, $requestCounter);
                $currentRequestItems = [];
            }

            // Add item to current request
            if ($this->isValidRequestItem($rowData)) {
                $currentRequestItems[] = $this->createRequestItem(
                    $rowData,
                    count($currentRequestItems),
                    $validationResult['assigned_user']
                );
            }
        }

        // Don't forget the last request
        if (!empty($currentRequestItems)) {
            $parsedRequests[] = $this->createRequestFromItems(
                $currentRequestTitle ?: "Imported Request {$requestCounter}",
                $currentRequestItems,
                $project,
                $client,
                $currentRequestor
            );
        }

        // Show warnings instead of throwing errors for user issues
        if (!empty($errors)) {
            $warningMessage = "Some user references could not be resolved:\n" . implode("\n", $errors);
        }

        return [
            'requests' => $parsedRequests,
            'project' => $project,
            'client' => $client,
            'available_users' => $availableUsers,
            'client_users' => $clientUsers,
            'stats' => [
                'total_requests' => count($parsedRequests),
                'total_items' => array_sum(array_map(fn($req) => count($req['items']), $parsedRequests)),
                'cf_items' => $this->countItemsByCategory($parsedRequests, 'CF'),
                'pf_items' => $this->countItemsByCategory($parsedRequests, 'PF'),
            ],
            'warnings' => empty($errors) ? [] : [$warningMessage ?? '']
        ];
    }

    /**
     * Execute the import after preview confirmation
     */
    public function executeImport(array $parsedData, int $createdBy): array
    {
        $createdRequests = 0;
        $createdItems = 0;

        DB::transaction(function () use ($parsedData, $createdBy, &$createdRequests, &$createdItems) {
            foreach ($parsedData['requests'] as $requestData) {
                // Create PBC request
                $pbcRequest = PbcRequest::create([
                    'title' => $requestData['title'],
                    'description' => $requestData['description'],
                    'client_id' => $requestData['client_id'],
                    'project_id' => $requestData['project_id'],
                    'due_date' => $requestData['due_date'],
                    'header_info' => $requestData['header_info'],
                    'status' => 'pending',
                    'created_by' => $requestData['requestor_user_id'] ?? $createdBy,
                ]);

                $createdRequests++;

                // Create request items
                foreach ($requestData['items'] as $itemData) {
                    PbcRequestItem::create([
                        'pbc_request_id' => $pbcRequest->id,
                        'category' => $itemData['category'],
                        'particulars' => $itemData['particulars'],
                        'date_requested' => $itemData['date_requested'],
                        'is_required' => $itemData['is_required'],
                        'status' => 'pending',
                        'order_index' => $itemData['order_index'],
                        'remarks' => $itemData['remarks'] ?? null,
                    ]);

                    $createdItems++;
                }
            }
        });

        return [
            'created_requests' => $createdRequests,
            'created_items' => $createdItems,
        ];
    }

    /**
     * Download Excel template with user examples
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'Category',
            'Request Description',
            'Requestor',
            'Date Requested',
            'Assigned to',
            'Status'
        ];

        // Add headers to worksheet
        foreach ($headers as $index => $header) {
            $worksheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        // Add sample data with actual user examples
        $sampleData = [
            ['CF', 'Bank statements and reconciliations', 'MNGR 1', '25/07/2025', 'ABC Corp Client', 'Pending'],
            ['CF', 'Trial balance as of year-end', 'Jane Manager', '25/07/2025', 'client@test.com', 'Uploaded'],
            ['PF', 'Articles of Incorporation', 'EYM', '25/07/2025', 'Client', 'Completed'],
            ['PF', 'BIR Certificate of Registration', 'John Partner', '25/07/2025', 'Client Staff 1', 'Overdue'],
        ];

        foreach ($sampleData as $rowIndex => $rowData) {
            foreach ($rowData as $colIndex => $value) {
                $worksheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 2, $value);
            }
        }

        // Style the headers
        $worksheet->getStyle('A1:F1')->getFont()->setBold(true);
        $worksheet->getStyle('A1:F1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create writer and download
        $writer = new Xlsx($spreadsheet);
        $filename = 'pbc_import_template_' . date('Y-m-d') . '.xlsx';

        $tempFile = tempnam(sys_get_temp_dir(), 'pbc_template');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend();
    }

    // Private helper methods
    private function getAvailableUsers(Project $project): array
    {
        $users = User::whereIn('role', [
            User::ROLE_SYSTEM_ADMIN,
            User::ROLE_ENGAGEMENT_PARTNER,
            User::ROLE_MANAGER,
            User::ROLE_ASSOCIATE
        ])->get();

        $userLookup = [];

        foreach ($users as $user) {
            // Add by exact name
            $userLookup[strtolower($user->name)] = $user;

            // Add by email
            $userLookup[strtolower($user->email)] = $user;

            // Add common abbreviations for project roles
            if ($project->engagement_partner_id === $user->id) {
                $userLookup['ep'] = $user;
                $userLookup['engagement partner'] = $user;
                $userLookup['eym'] = $user;
            }

            if ($project->manager_id === $user->id) {
                $userLookup['manager'] = $user;
                $userLookup['mngr'] = $user;
                $userLookup['mngr 1'] = $user;
                $userLookup['mgr'] = $user;
            }

            // Add by role-based patterns
            if ($user->isAssociate()) {
                $userLookup['staff'] = $user;
                $userLookup['staff 1'] = $user;
                $userLookup['staff 2'] = $user;
                $userLookup['associate'] = $user;
            }
        }

        return $userLookup;
    }

    private function getClientUsers(Client $client): array
    {
        $users = collect([$client->user]);
        $userLookup = [];

        foreach ($users as $user) {
            if ($user) {
                $userLookup[strtolower($user->name)] = $user;
                $userLookup[strtolower($user->email)] = $user;

                // Add common client patterns
                $userLookup['client'] = $user;
                $userLookup['client staff'] = $user;
                $userLookup['client staff 1'] = $user;
                $userLookup['client staff 2'] = $user;
                $userLookup['client contact'] = $user;
            }
        }

        return $userLookup;
    }

    private function validateAndResolveUsers(array $rowData, array $availableUsers, array $clientUsers, int $excelRowNumber): array
    {
        $errors = [];
        $requestorUser = null;
        $assignedUser = null;

        // Resolve Requestor (MTC Staff)
        $requestorText = strtolower(trim($rowData['requestor'] ?? ''));
        if (!empty($requestorText)) {
            if (isset($availableUsers[$requestorText])) {
                $requestorUser = $availableUsers[$requestorText];
            } else {
                // Try partial matching
                $requestorUser = $this->findUserByPartialMatch($requestorText, $availableUsers);

                if (!$requestorUser) {
                    $errors[] = "Row {$excelRowNumber}: Requestor '{$rowData['requestor']}' not found.";
                }
            }
        }

        // Resolve Assigned To (Client User)
        $assignedText = strtolower(trim($rowData['assigned_to'] ?? ''));
        if (!empty($assignedText)) {
            if (isset($clientUsers[$assignedText])) {
                $assignedUser = $clientUsers[$assignedText];
            } else {
                // Try partial matching
                $assignedUser = $this->findUserByPartialMatch($assignedText, $clientUsers);

                if (!$assignedUser) {
                    $errors[] = "Row {$excelRowNumber}: Assigned to '{$rowData['assigned_to']}' not found.";
                }
            }
        }

        return [
            'requestor_user' => $requestorUser,
            'assigned_user' => $assignedUser,
            'errors' => $errors
        ];
    }

    private function findUserByPartialMatch(string $searchText, array $userLookup): ?User
    {
        foreach ($userLookup as $key => $user) {
            if (str_contains($key, $searchText) || str_contains($searchText, $key)) {
                return $user;
            }
        }
        return null;
    }

    private function isNewRequestRow(array $rowData, array $currentItems, ?User $currentRequestor): bool
    {
        // Simple logic: every 10 items or different requestor
        if (count($currentItems) >= 10) {
            return true;
        }

        if (!$currentRequestor) {
            return true;
        }

        $newRequestorText = strtolower(trim($rowData['requestor'] ?? ''));
        $currentRequestorText = strtolower($currentRequestor->name);

        return $newRequestorText !== $currentRequestorText && !empty($newRequestorText);
    }

    private function isValidRequestItem(array $rowData): bool
    {
        return !empty($rowData['category']) &&
               !empty($rowData['request_description']) &&
               in_array(strtoupper($rowData['category']), ['CF', 'PF']);
    }

    private function createRequestItem(array $rowData, int $orderIndex, ?User $assignedUser): array
    {
        return [
            'category' => strtoupper($rowData['category']),
            'particulars' => $rowData['request_description'],
            'date_requested' => $this->parseDate($rowData['date_requested']),
            'is_required' => true,
            'order_index' => $orderIndex,
            'remarks' => $rowData['status'] !== 'Pending' ? "Imported with status: {$rowData['status']}" : null,
            'assigned_user_id' => $assignedUser?->id,
            'assigned_user_name' => $assignedUser?->name,
        ];
    }

    private function createRequestFromItems(string $title, array $items, Project $project, Client $client, ?User $requestor): array
    {
        return [
            'title' => $title,
            'description' => "Imported PBC request for {$project->engagement_name}",
            'client_id' => $client->id,
            'project_id' => $project->id,
            'due_date' => now()->addDays(30)->toDateString(),
            'header_info' => [
                'engagement_partner' => $project->engagementPartner?->name ?? 'EYM',
                'manager' => $project->manager?->name ?? 'MNGR 1',
                'requestor' => $requestor?->name ?? 'Unknown',
                'requestor_id' => $requestor?->id,
                'imported_at' => now()->toDateTimeString(),
            ],
            'requestor_user_id' => $requestor?->id,
            'items' => $items,
        ];
    }

    private function generateRequestTitle(array $rowData, Project $project, ?User $requestor, int $counter): string
    {
        $requestorName = $requestor?->name ?? ($rowData['requestor'] ?? 'Staff');
        return "{$project->engagement_name} - Request {$counter} by {$requestorName}";
    }

    private function findHeaderRow(array $rows, array $expectedHeaders): int
    {
        foreach ($rows as $index => $row) {
            if (empty($row)) continue;

            $matchedHeaders = 0;
            foreach ($expectedHeaders as $expectedHeader) {
                foreach ($row as $cell) {
                    if (stripos($cell, $expectedHeader) !== false) {
                        $matchedHeaders++;
                        break;
                    }
                }
            }

            if ($matchedHeaders >= 3) {
                return $index;
            }
        }

        return -1;
    }

    private function mapRowToData(array $headers, array $row, array $expectedHeaders): array
    {
        $data = [];

        foreach ($expectedHeaders as $expectedHeader) {
            $value = '';

            foreach ($headers as $index => $header) {
                if (stripos($header, $expectedHeader) !== false) {
                    $value = $row[$index] ?? '';
                    break;
                }
            }

            $data[strtolower(str_replace(' ', '_', $expectedHeader))] = trim($value);
        }

        return $data;
    }

    private function parseDate(string $dateString): string
    {
        if (empty($dateString)) {
            return now()->toDateString();
        }

        try {
            $formats = ['d/m/Y', 'Y-m-d', 'm/d/Y', 'd-m-Y'];

            foreach ($formats as $format) {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date) {
                    return $date->toDateString();
                }
            }

            return Carbon::parse($dateString)->toDateString();
        } catch (\Exception $e) {
            return now()->toDateString();
        }
    }

    private function countItemsByCategory(array $requests, string $category): int
    {
        $count = 0;
        foreach ($requests as $request) {
            foreach ($request['items'] as $item) {
                if ($item['category'] === $category) {
                    $count++;
                }
            }
        }
        return $count;
    }
}
