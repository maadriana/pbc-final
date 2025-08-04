<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Client;
use App\Models\User;
use App\Models\ProjectAssignment;
use App\Services\ProjectAssignmentService;
use App\Services\JobGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    protected $assignmentService;
    protected $jobGenerationService;

    public function __construct(ProjectAssignmentService $assignmentService, JobGenerationService $jobGenerationService)
    {
        $this->assignmentService = $assignmentService;
        $this->jobGenerationService = $jobGenerationService;
    }

    public function index(Request $request)
    {
        $query = Project::with(['client', 'creator', 'assignments.user']);

        // Apply user access filtering - use the existing scope
        $query->forUser(auth()->user());

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('job_id', 'like', "%{$search}%")
                  ->orWhere('engagement_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by client (only accessible clients)
        if ($request->filled('client_id')) {
            $accessibleClientIds = $this->getAccessibleClients()->pluck('id');
            if ($accessibleClientIds->contains($request->client_id)) {
                $query->where('client_id', $request->client_id);
            }
        }

        $projects = $query->latest()->paginate(15);
        $clients = $this->getAccessibleClients();

        return view('admin.projects.index', compact('projects', 'clients'));
    }

    public function show(Project $project)
    {
        // Check user access using existing model method
        if (!$project->canUserAccess(auth()->user())) {
            abort(403, 'You do not have access to this project.');
        }

        $project->load([
            'client.user',
            'creator',
            'assignments.user',
            'pbcRequests' => function($query) {
                $query->latest();
            }
        ]);

        // Get available clients for assignment (but since each project has one client, this might not be needed)
        $availableClients = collect(); // Empty collection since we're using single client model

        return view('admin.projects.show', compact('project', 'availableClients'));
    }

    public function edit(Project $project)
    {
        // Check user access using existing model method
        if (!$project->canUserAccess(auth()->user())) {
            abort(403, 'You do not have access to this project.');
        }

        $clients = $this->getAccessibleClients();
        $staffByRole = $this->assignmentService->getAvailableStaff();
        $project->load('assignments');

        // Get suggested job ID for the form
        $suggestedJobId = $project->getSuggestedJobId();

        return view('admin.projects.edit', compact('project', 'clients', 'staffByRole', 'suggestedJobId'));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'job_id' => [
                'nullable',
                'string',
                'max:50',
                'unique:projects,job_id,' . $project->id,
                function ($attribute, $value, $fail) {
                    if ($value && !$this->jobGenerationService->isValidJobId($value)) {
                        $fail('The job ID format is invalid. Expected format: ABC-22-001-A-24');
                    }
                },
            ],
            'engagement_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'engagement_type' => 'required|in:audit,accounting,tax,special_engagement,others',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'engagement_period_start' => 'nullable|date',
            'engagement_period_end' => 'nullable|date|after_or_equal:engagement_period_start',
            'status' => 'required|in:active,on_hold,completed,cancelled',
            'engagement_partner' => 'nullable|exists:users,id',
            'manager' => 'nullable|exists:users,id',
            'associate_1' => 'nullable|exists:users,id',
            'associate_2' => 'nullable|exists:users,id',
        ]);

        // Generate job ID if not provided
        $jobId = $request->job_id;
        if (empty($jobId)) {
            $jobYear = null;
            if ($request->engagement_period_start) {
                $jobYear = \Carbon\Carbon::parse($request->engagement_period_start)->year;
            }

            $jobId = $this->jobGenerationService->generateUniqueJobId(
                $request->client_id,
                $request->engagement_type,
                $jobYear,
                $project->id
            );
        }

        // Update project basic information
        $project->update([
            'job_id' => $jobId,
            'engagement_name' => $request->engagement_name,
            'name' => $request->engagement_name, // Use engagement_name as name
            'description' => $request->description,
            'client_id' => $request->client_id,
            'engagement_type' => $request->engagement_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'engagement_period_start' => $request->engagement_period_start,
            'engagement_period_end' => $request->engagement_period_end,
            'status' => $request->status,
        ]);

        $this->updateTeamAssignments($project, $request);

        // Redirect to projects index instead of show
        return redirect()->route('admin.projects.index')
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        // Check user access using existing model method
        if (!$project->canUserAccess(auth()->user())) {
            abort(403, 'You do not have access to this project.');
        }

        // Check if project has PBC requests
        if ($project->pbcRequests()->count() > 0) {
            return redirect()
                ->route('admin.projects.index')
                ->with('error', 'Cannot delete project with existing PBC requests.');
        }

        $project->delete();

        return redirect()
            ->route('admin.projects.index')
            ->with('success', 'Project deleted successfully.');
    }

    /**
     * Assign a team member to a project
     */
    public function assignTeamMember(Request $request, Project $project)
    {
        // Check user access
        if (!$project->canUserAccess(auth()->user())) {
            abort(403, 'You do not have access to this project.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:engagement_partner,manager,associate_1,associate_2',
        ]);

        $user = User::findOrFail($request->user_id);

        // Use the assignment service
        $result = $this->assignmentService->assignTeamMember($project, $user, $request->role);

        if ($result) {
            return redirect()
                ->route('admin.projects.show', $project)
                ->with('success', "User {$user->name} assigned as {$request->role} successfully.");
        }

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('error', 'Failed to assign team member. Role may already be taken.');
    }

    /**
     * Remove a team member from a project
     */
    public function removeTeamMember(Request $request, Project $project)
    {
        // Check user access
        if (!$project->canUserAccess(auth()->user())) {
            abort(403, 'You do not have access to this project.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        // Use the assignment service
        $result = $this->assignmentService->removeTeamMember($project, $user);

        if ($result) {
            return redirect()
                ->route('admin.projects.show', $project)
                ->with('success', "User {$user->name} removed from project successfully.");
        }

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('error', 'Failed to remove team member.');
    }

    /**
     * Get projects accessible to current user
     */
    private function getAccessibleProjects()
    {
        return Project::forUser(auth()->user())
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get clients accessible to current user based on project assignments
     */
    private function getAccessibleClients()
    {
        if (auth()->user()->isSystemAdmin()) {
            return Client::with('user')->orderBy('company_name')->get();
        }

        // Get clients from projects the user is assigned to
        $accessibleProjects = Project::forUser(auth()->user())->pluck('client_id')->unique();

        return Client::with('user')
            ->whereIn('id', $accessibleProjects)
            ->orderBy('company_name')
            ->get();
    }

    /**
     * Check if current user can access the given client
     */
    private function canAccessClient(Client $client)
    {
        if (auth()->user()->isSystemAdmin()) {
            return true;
        }

        // Check if user has access to any projects for this client
        return Project::forUser(auth()->user())
            ->where('client_id', $client->id)
            ->exists();
    }

    public function create(Request $request)
    {
        // Check permission
        if (!auth()->user()->canCreateProjects()) {
            abort(403, 'You do not have permission to create projects.');
        }

        // Handle pre-selected client from wireframe workflow
        $preselectedClientId = $request->get('client_id');
        $preselectedClient = null;
        $suggestedJobId = null;

        if ($preselectedClientId) {
            $preselectedClient = Client::find($preselectedClientId);
            // Verify user can access this client
            if ($preselectedClient && !$this->canAccessClient($preselectedClient)) {
                abort(403, 'You do not have access to this client.');
            }
        }

        $clients = $this->getAccessibleClients();
        $staffByRole = $this->assignmentService->getAvailableStaff();

        return view('admin.projects.create', compact(
            'clients', 'staffByRole', 'preselectedClient', 'suggestedJobId'
        ));
    }

    // Updated store method with new job ID generation
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'job_id' => [
                'nullable',
                'string',
                'max:50',
                'unique:projects,job_id',
                function ($attribute, $value, $fail) {
                    if ($value && !$this->jobGenerationService->isValidJobId($value)) {
                        $fail('The job ID format is invalid. Expected format: ABC-22-001-A-24');
                    }
                },
            ],
            'engagement_name' => 'required|string|max:255',
            'engagement_type' => 'required|string|in:audit,accounting,tax,special_engagement,others',
            'engagement_period_start' => 'nullable|date',
            'engagement_period_end' => 'nullable|date|after_or_equal:engagement_period_start',
            'engagement_partner_id' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'associate_1' => 'nullable|exists:users,id',
            'associate_2' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,on_hold,completed,cancelled',
        ]);

        // Set created_by
        $validatedData['created_by'] = auth()->id();
        $validatedData['description'] = null;

        // Generate job ID if not provided
        if (empty($validatedData['job_id'])) {
            $jobYear = null;
            if (!empty($validatedData['engagement_period_start'])) {
                $jobYear = \Carbon\Carbon::parse($validatedData['engagement_period_start'])->year;
            }

            $validatedData['job_id'] = $this->jobGenerationService->generateUniqueJobId(
                $validatedData['client_id'],
                $validatedData['engagement_type'],
                $jobYear
            );
        }

        try {
            // Create the project
            $project = Project::create($validatedData);

            // Handle team assignments if provided
            if (!empty($validatedData['associate_1'])) {
                ProjectAssignment::create([
                    'project_id' => $project->id,
                    'user_id' => $validatedData['associate_1'],
                    'role' => ProjectAssignment::ROLE_ASSOCIATE_1,
                ]);
            }

            if (!empty($validatedData['associate_2'])) {
                ProjectAssignment::create([
                    'project_id' => $project->id,
                    'user_id' => $validatedData['associate_2'],
                    'role' => ProjectAssignment::ROLE_ASSOCIATE_2,
                ]);
            }

            // Sync team assignments
            $project->syncTeamAssignments();

            if (request()->has('client_id') && request('from') === 'client') {
                return redirect()
                    ->route('admin.clients.show', $validatedData['client_id'])
                    ->with('success', 'Project created successfully with Job ID: ' . $project->job_id);
            }

            return redirect()
                ->route('admin.projects.index')
                ->with('success', 'Project created successfully with Job ID: ' . $project->job_id);

        } catch (\Exception $e) {
            \Log::error('Project creation failed: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create project. Please try again.');
        }
    }

    /**
     * Get suggested job ID via AJAX
     */
    public function getSuggestedJobId(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'engagement_type' => 'required|in:audit,accounting,tax,special_engagement,others',
            'engagement_period_start' => 'nullable|date',
        ]);

        $jobYear = null;
        if ($request->engagement_period_start) {
            $jobYear = \Carbon\Carbon::parse($request->engagement_period_start)->year;
        }

        $suggestedJobId = $this->jobGenerationService->generateUniqueJobId(
            $request->client_id,
            $request->engagement_type,
            $jobYear
        );

        return response()->json([
            'success' => true,
            'job_id' => $suggestedJobId,
            'breakdown' => $this->jobGenerationService->parseJobId($suggestedJobId)
        ]);
    }

    /**
     * Validate job ID format via AJAX
     */
    public function validateJobId(Request $request)
    {
        $request->validate([
            'job_id' => 'required|string',
            'project_id' => 'nullable|exists:projects,id'
        ]);

        $jobId = $request->job_id;
        $projectId = $request->project_id;

        $isValid = $this->jobGenerationService->isValidJobId($jobId);
        $isUnique = $this->jobGenerationService->isJobIdUnique($jobId, $projectId);

        $response = [
            'valid' => $isValid,
            'unique' => $isUnique,
            'message' => ''
        ];

        if (!$isValid) {
            $response['message'] = 'Invalid Job ID format. Expected format: ABC-22-001-A-24';
        } elseif (!$isUnique) {
            $response['message'] = 'Job ID already exists. Please use a different ID.';
        } else {
            $response['message'] = 'Job ID is valid and available.';
            $response['breakdown'] = $this->jobGenerationService->parseJobId($jobId);
        }

        return response()->json($response);
    }

    // Helper method to update team assignments
    private function updateTeamAssignments(Project $project, Request $request)
    {
        $roles = ['engagement_partner', 'manager', 'associate_1', 'associate_2'];

        foreach ($roles as $role) {
            $userId = $request->input($role);

            // Remove existing assignment for this role
            $project->assignments()->where('role', $role)->delete();

            // Add new assignment if user is selected
            if ($userId) {
                $project->assignments()->create([
                    'user_id' => $userId,
                    'role' => $role,
                    'assigned_by' => auth()->id(),
                ]);
            }
        }
    }
}
