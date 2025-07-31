<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PbcRequest;
use App\Models\PbcRequestItem;
use App\Models\PbcTemplate;
use App\Models\Client;
use App\Models\Project;
use App\Models\DocumentUpload;
use App\Http\Requests\StorePbcRequestRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PbcRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = PbcRequest::with(['client', 'project', 'creator']);

        if (!auth()->user()->isSystemAdmin()) {
            $assignedProjectIds = auth()->user()->assignedProjects()->pluck('projects.id');
            $query->whereIn('project_id', $assignedProjectIds);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('company_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('project', function($projectQuery) use ($search) {
                      $projectQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('job_id', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by client
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $requests = $query->latest()->paginate(15);
        $clients = $this->getAccessibleClients();

        $importStats = [
            'can_import' => auth()->user()->canCreatePbcRequests(),
            'total_imported_today' => PbcRequest::whereDate('created_at', today())->count(),
            'pending_imports' => session()->has('import_preview_data'),
        ];

        return view('admin.pbc-requests.index', compact('requests', 'clients', 'importStats'));
    }

    public function create()
    {
        if (!auth()->user()->canCreatePbcRequests()) {
            abort(403, 'You do not have permission to create PBC requests.');
        }

        $templates = PbcTemplate::where('is_active', true)->orderBy('name')->get();
        $clients = $this->getAccessibleClients();
        $projects = $this->getAccessibleProjects();

        return view('admin.pbc-requests.create', compact('templates', 'clients', 'projects'));
    }

    public function store(StorePbcRequestRequest $request)
    {
        if (!auth()->user()->canCreatePbcRequests()) {
            abort(403, 'You do not have permission to create PBC requests.');
        }

        if (!$this->canAccessProject($request->project_id)) {
            abort(403, 'You do not have access to this project.');
        }

        DB::transaction(function () use ($request) {
            $pbcRequest = PbcRequest::create([
                'template_id' => $request->template_id,
                'client_id' => $request->client_id,
                'project_id' => $request->project_id,
                'title' => $request->title,
                'description' => $request->description,
                'header_info' => $request->header_info,
                'due_date' => $request->due_date,
                'created_by' => auth()->id(),
            ]);

            if ($request->items && is_array($request->items)) {
                foreach ($request->items as $index => $item) {
                    if (isset($item['particulars']) && !empty(trim($item['particulars']))) {
                        PbcRequestItem::create([
                            'pbc_request_id' => $pbcRequest->id,
                            'category' => $item['category'] ?? null,
                            'particulars' => trim($item['particulars']),
                            'date_requested' => $item['date_requested'] ?? now()->toDateString(),
                            'is_required' => isset($item['is_required']) ? (bool)$item['is_required'] : true,
                            'remarks' => $item['remarks'] ?? null,
                            'order_index' => $index,
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route('admin.pbc-requests.index')
            ->with('success', 'PBC Request created successfully.');
    }

    public function show(PbcRequest $pbcRequest)
    {
        if (!$this->canAccessPbcRequest($pbcRequest)) {
            abort(403, 'You do not have permission to view this PBC request.');
        }

        $pbcRequest->load([
            'client.user',
            'project',
            'creator',
            'template',
            'items' => function($query) {
                $query->orderBy('order_index');
            },
            'items.documents.uploader'
        ]);

        return view('admin.pbc-requests.show', compact('pbcRequest'));
    }

    public function edit(PbcRequest $pbcRequest)
    {
        if (!$this->canAccessPbcRequest($pbcRequest) || !auth()->user()->canCreatePbcRequests()) {
            abort(403, 'You do not have permission to edit this PBC request.');
        }

        if ($pbcRequest->sent_at) {
            return redirect()
                ->route('admin.pbc-requests.show', $pbcRequest)
                ->with('error', 'Cannot edit request that has been sent to client.');
        }

        $pbcRequest->load(['items' => function($query) {
            $query->orderBy('order_index');
        }]);

        $templates = PbcTemplate::where('is_active', true)->orderBy('name')->get();
        $clients = $this->getAccessibleClients();
        $projects = $this->getAccessibleProjects();

        return view('admin.pbc-requests.edit', compact('pbcRequest', 'templates', 'clients', 'projects'));
    }

    public function update(StorePbcRequestRequest $request, PbcRequest $pbcRequest)
    {
        if (!$this->canAccessPbcRequest($pbcRequest) || !auth()->user()->canCreatePbcRequests()) {
            abort(403, 'You do not have permission to update this PBC request.');
        }

        if ($pbcRequest->sent_at) {
            return redirect()
                ->route('admin.pbc-requests.show', $pbcRequest)
                ->with('error', 'Cannot update request that has been sent to client.');
        }

        if (!$this->canAccessProject($request->project_id)) {
            abort(403, 'You do not have access to this project.');
        }

        DB::transaction(function () use ($request, $pbcRequest) {
            $pbcRequest->update([
                'template_id' => $request->template_id,
                'client_id' => $request->client_id,
                'project_id' => $request->project_id,
                'title' => $request->title,
                'description' => $request->description,
                'header_info' => $request->header_info,
                'due_date' => $request->due_date,
            ]);

            $pbcRequest->items()->delete();

            if ($request->items && is_array($request->items)) {
                foreach ($request->items as $index => $item) {
                    if (isset($item['particulars']) && !empty(trim($item['particulars']))) {
                        PbcRequestItem::create([
                            'pbc_request_id' => $pbcRequest->id,
                            'category' => $item['category'] ?? null,
                            'particulars' => trim($item['particulars']),
                            'date_requested' => $item['date_requested'] ?? now()->toDateString(),
                            'is_required' => isset($item['is_required']) ? (bool)$item['is_required'] : true,
                            'remarks' => $item['remarks'] ?? null,
                            'order_index' => $index,
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route('admin.pbc-requests.show', $pbcRequest)
            ->with('success', 'PBC Request updated successfully.');
    }

    public function destroy(PbcRequest $pbcRequest)
    {
        if (!$this->canAccessPbcRequest($pbcRequest)) {
            abort(403, 'You do not have permission to delete this PBC request.');
        }

        $hasDocuments = $pbcRequest->items()
            ->whereHas('documents')
            ->exists();

        if ($hasDocuments) {
            return redirect()
                ->route('admin.pbc-requests.index')
                ->with('error', 'Cannot delete request with uploaded documents.');
        }

        $pbcRequest->delete();

        return redirect()
            ->route('admin.pbc-requests.index')
            ->with('success', 'PBC Request deleted successfully.');
    }

    public function send(PbcRequest $pbcRequest)
    {
        if (!$this->canAccessPbcRequest($pbcRequest)) {
            abort(403, 'You do not have permission to send this PBC request.');
        }

        if ($pbcRequest->sent_at) {
            return redirect()
                ->route('admin.pbc-requests.show', $pbcRequest)
                ->with('error', 'Request has already been sent to client.');
        }

        $pbcRequest->update([
            'sent_at' => now(),
            'status' => 'in_progress',
        ]);

        return redirect()
            ->route('admin.pbc-requests.show', $pbcRequest)
            ->with('success', 'PBC Request sent to client successfully.');
    }

    public function reviewItem(Request $request, PbcRequest $pbcRequest, PbcRequestItem $item)
    {
        if (!$this->canAccessPbcRequest($pbcRequest) || !auth()->user()->canReviewDocuments()) {
            abort(403, 'You do not have permission to review documents.');
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'admin_notes' => 'nullable|string|max:500',
            'document_id' => 'required|exists:document_uploads,id'
        ]);

        $document = DocumentUpload::findOrFail($request->document_id);

        if ($document->pbc_request_item_id !== $item->id) {
            abort(403, 'Document does not belong to this item.');
        }

        $status = $request->action === 'approve' ? 'approved' : 'rejected';

        DB::transaction(function () use ($item, $document, $status, $request) {
            $document->update([
                'status' => $status,
                'admin_notes' => $request->admin_notes,
                'approved_at' => $status === 'approved' ? now() : null,
                'approved_by' => auth()->id(),
            ]);

            if ($status === 'approved') {
                $item->update([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewed_by' => auth()->id(),
                ]);
            } else {
                $hasApprovedDocs = $item->documents()->where('status', 'approved')->exists();

                if (!$hasApprovedDocs) {
                    $hasUploadedDocs = $item->documents()->where('status', 'uploaded')->exists();

                    $item->update([
                        'status' => $hasUploadedDocs ? 'uploaded' : 'rejected',
                        'reviewed_at' => $hasUploadedDocs ? $item->reviewed_at : now(),
                        'reviewed_by' => auth()->id(),
                    ]);
                }
            }
        });

        $this->updateRequestStatus($pbcRequest);

        $action = $status === 'approved' ? 'approved' : 'rejected';
        return redirect()
            ->route('admin.pbc-requests.show', $pbcRequest)
            ->with('success', "Document '{$document->original_filename}' {$action} successfully.");
    }

    /**
     * Project-specific PBC request index page
     */
    public function projectIndex(Client $client, Project $project)
{
    if (!$this->canAccessClient($client) || !$this->canAccessProject($project->id)) {
        abort(403, 'You do not have permission to view this project.');
    }

    if ($project->client_id !== $client->id) {
        abort(404, 'Project not found for this client.');
    }

    // Get all PBC request items for this project (flatten the structure)
    $requests = PbcRequest::with(['creator', 'items.documents'])
        ->where('client_id', $client->id)
        ->where('project_id', $project->id)
        ->latest()
        ->get(); // Using get() instead of paginate() to avoid hasPages error

    // Calculate stats
    $stats = [
        'total_requests' => $requests->sum(function($request) {
            return $request->items->count();
        }),
        'pending' => $requests->sum(function($request) {
            return $request->items->where('status', 'pending')->count();
        }),
        'in_progress' => $requests->sum(function($request) {
            return $request->items->filter(function($item) {
                return $item->getCurrentStatus() === 'uploaded';
            })->count();
        }),
        'completed' => $requests->sum(function($request) {
            return $request->items->filter(function($item) {
                return $item->getCurrentStatus() === 'approved';
            })->count();
        }),
    ];

    return view('admin.clients.projects.pbc-requests.index', compact(
        'client', 'project', 'requests', 'stats'
    ));
}

    /**
     * Project-specific PBC request creation page
     */
    public function projectCreate(Client $client, Project $project)
    {
        if (!auth()->user()->canCreatePbcRequests()) {
            abort(403, 'You do not have permission to create PBC requests.');
        }

        if (!$this->canAccessClient($client) || !$this->canAccessProject($project->id)) {
            abort(403, 'You do not have permission to access this project.');
        }

        if ($project->client_id !== $client->id) {
            abort(404, 'Project not found for this client.');
        }

        $templates = PbcTemplate::where('is_active', true)->orderBy('name')->get();

        return view('admin.clients.projects.pbc-requests.create', compact(
            'client', 'project', 'templates'
        ));
    }

    /**
     * Store project-specific PBC request
     */
    public function projectStore(Request $request, Client $client, Project $project)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date|after_or_equal:today',
            'template_id' => 'nullable|exists:pbc_templates,id',
            'items' => 'required|array|min:1',
            'items.*.category' => 'required|in:PF,CF',
            'items.*.particulars' => 'required|string|max:500',
            'items.*.assigned_to' => 'nullable|string|max:255',
            'items.*.due_date' => 'nullable|date',
            'items.*.is_required' => 'nullable|boolean',
        ]);

        if (!auth()->user()->canCreatePbcRequests()) {
            return redirect()->back()
                ->with('error', 'You do not have permission to create PBC requests.');
        }

        if (!$this->canAccessClient($client) || !$this->canAccessProject($project->id)) {
            return redirect()->back()
                ->with('error', 'You do not have permission to access this project.');
        }

        if ($project->client_id !== $client->id) {
            return redirect()->back()
                ->with('error', 'Project not found for this client.');
        }

        try {
            DB::transaction(function () use ($validatedData, $client, $project) {
                $pbcRequest = PbcRequest::create([
                    'template_id' => $validatedData['template_id'] ?? null,
                    'client_id' => $client->id,
                    'project_id' => $project->id,
                    'title' => $validatedData['title'],
                    'description' => $validatedData['description'] ?? null,
                    'due_date' => $validatedData['due_date'] ?? null,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]);

                foreach ($validatedData['items'] as $index => $item) {
                    if (!empty(trim($item['particulars']))) {
                        PbcRequestItem::create([
                            'pbc_request_id' => $pbcRequest->id,
                            'category' => $item['category'],
                            'particulars' => trim($item['particulars']),
                            'assigned_to' => $item['assigned_to'] ?? null,
                            'date_requested' => now()->toDateString(),
                            'due_date' => !empty($item['due_date']) ? $item['due_date'] : null,
                            'is_required' => isset($item['is_required']) ? true : false,
                            'status' => 'pending',
                            'order_index' => $index,
                            'requestor' => auth()->user()->name,
                        ]);
                    }
                }

                Log::info('PBC Request created successfully', [
                    'pbc_request_id' => $pbcRequest->id,
                    'client_id' => $client->id,
                    'project_id' => $project->id,
                    'items_count' => count($validatedData['items']),
                    'created_by' => auth()->id()
                ]);
            });

            return redirect()
                ->route('admin.clients.projects.pbc-requests.index', [$client, $project])
                ->with('success', 'PBC Request created successfully! Items have been added to the project list.');

        } catch (\Exception $e) {
            Log::error('Error creating PBC Request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'client_id' => $client->id,
                'project_id' => $project->id,
                'user_id' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to create PBC Request. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show project import form
     */
    public function projectImport(Client $client, Project $project)
    {
        if (!auth()->user()->canCreatePbcRequests()) {
            abort(403, 'You do not have permission to import PBC requests.');
        }

        if (!$this->canAccessClient($client) || !$this->canAccessProject($project->id)) {
            abort(403, 'You do not have permission to access this project.');
        }

        if ($project->client_id !== $client->id) {
            abort(404, 'Project not found for this client.');
        }

        return view('admin.clients.projects.pbc-requests.import', compact('client', 'project'));
    }

    /**
     * Preview project import
     */
    public function projectImportPreview(Request $request, Client $client, Project $project)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date|after_or_equal:today',
        ]);

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
     * Execute project import
     */
    public function projectImportExecute(Request $request, Client $client, Project $project)
    {
        if (!session()->has('import_preview_data')) {
            return response()->json([
                'success' => false,
                'message' => 'No preview data found. Please preview first.'
            ]);
        }

        $previewData = session('import_preview_data');

        if (!$this->canAccessClient($client) || !$this->canAccessProject($project->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this project.'
            ], 403);
        }

        try {
            DB::transaction(function () use ($previewData, $client, $project) {
                $pbcRequest = PbcRequest::create([
                    'client_id' => $client->id,
                    'project_id' => $project->id,
                    'title' => $previewData['title'],
                    'description' => $previewData['description'],
                    'due_date' => $previewData['due_date'],
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]);

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

    // Helper methods
    private function updateRequestStatus(PbcRequest $pbcRequest)
    {
        $totalItems = $pbcRequest->items()->count();

        if ($totalItems === 0) {
            return;
        }

        $items = $pbcRequest->items()->with('documents')->get();
        $approvedItems = $items->filter(function ($item) {
            return $item->getCurrentStatus() === 'approved';
        })->count();

        if ($approvedItems === $totalItems) {
            $pbcRequest->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } else {
            $hasActivity = $items->filter(function ($item) {
                return in_array($item->getCurrentStatus(), ['uploaded', 'approved', 'rejected']);
            })->count() > 0;

            if ($hasActivity && $pbcRequest->status === 'pending') {
                $pbcRequest->update([
                    'status' => 'in_progress',
                ]);
            }
        }
    }

    private function canAccessPbcRequest(PbcRequest $pbcRequest)
    {
        if (auth()->user()->isSystemAdmin()) {
            return true;
        }

        return $this->canAccessProject($pbcRequest->project_id);
    }

    private function canAccessProject($projectId)
    {
        if (auth()->user()->isSystemAdmin()) {
            return true;
        }

        return auth()->user()->assignedProjects()->where('projects.id', $projectId)->exists();
    }

    private function canAccessClient(Client $client)
    {
        if (auth()->user()->isSystemAdmin()) {
            return true;
        }

        $userProjectIds = auth()->user()->assignedProjects()->pluck('projects.id');
        $clientProjectIds = $client->projects()->pluck('id');

        return $userProjectIds->intersect($clientProjectIds)->isNotEmpty();
    }

    private function getAccessibleProjects()
    {
        $query = Project::where('status', 'active');

        if (!auth()->user()->isSystemAdmin()) {
            $assignedProjectIds = auth()->user()->assignedProjects()->pluck('projects.id');
            $query->whereIn('id', $assignedProjectIds);
        }

        return $query->orderBy('name')->get();
    }

    private function getAccessibleClients()
    {
        $query = Client::with('user');

        if (!auth()->user()->isSystemAdmin()) {
            $assignedProjectIds = auth()->user()->assignedProjects()->pluck('projects.id');
            $query->whereHas('projects', function($q) use ($assignedProjectIds) {
                $q->whereIn('projects.id', $assignedProjectIds);
            });
        }

        return $query->orderBy('company_name')->get();
    }

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
                            $headerRow = $i + 1;
                            break;
                        }
                    }
                }

                // Process data rows
                for ($i = $headerRow; $i < count($rows); $i++) {
                    $row = $rows[$i];

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

    private function processCsvFile($file)
    {
        $data = [];
        $csvData = array_map('str_getcsv', file($file->getPathname()));

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
                return 'PF';
        }
    }

    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // Not a valid Excel date, try parsing as string
            }
        }

        try {
            $date = new \DateTime($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseBoolean($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtoupper(trim($value));
        return in_array($value, ['TRUE', 'YES', '1', 'REQUIRED', 'Y']);
    }
}
