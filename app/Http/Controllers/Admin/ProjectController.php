<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Client;
use App\Models\User;
use App\Models\ProjectAssignment;
use App\Services\ProjectAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    protected $assignmentService;

    public function __construct(ProjectAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
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

        return view('admin.projects.edit', compact('project', 'clients', 'staffByRole'));
    }

    public function update(Request $request, Project $project)
    {
        // Check user access using existing model method
        if (!$project->canUserAccess(auth()->user())) {
            abort(403, 'You do not have access to this project.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'engagement_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'engagement_type' => 'required|in:audit,accounting,tax,special_engagement,others',
            'engagement_period_start' => 'nullable|date',
            'engagement_period_end' => 'nullable|date|after_or_equal:engagement_period_start',
            'status' => 'required|in:active,completed,on_hold,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',

            // Team assignments
            'engagement_partner' => 'nullable|exists:users,id',
            'manager' => 'nullable|exists:users,id',
            'associate_1' => 'nullable|exists:users,id',
            'associate_2' => 'nullable|exists:users,id',
        ]);

        // Verify user can access the selected client
        $client = Client::findOrFail($request->client_id);
        if (!$this->canAccessClient($client)) {
            abort(403, 'You do not have access to this client.');
        }

        DB::transaction(function () use ($request, $project) {
            // Update project
            $project->update([
                'name' => $request->name,
                'engagement_name' => $request->engagement_name,
                'description' => $request->description,
                'client_id' => $request->client_id,
                'engagement_type' => $request->engagement_type,
                'engagement_period_start' => $request->engagement_period_start,
                'engagement_period_end' => $request->engagement_period_end,
                'status' => $request->status,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            // Update team assignments
            $assignments = [
                'engagement_partner' => $request->engagement_partner,
                'manager' => $request->manager,
                'associate_1' => $request->associate_1,
                'associate_2' => $request->associate_2,
            ];

            $this->assignmentService->updateProjectTeam($project, $assignments);
        });

        return redirect()
            ->route('admin.projects.show', $project)
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

    /**
     * Generate unique job ID based on engagement type
     */
    private function generateJobId($engagementType)
    {
        $typeMap = [
            'audit' => '1',
            'accounting' => '2',
            'tax' => '3',
            'special_engagement' => '4',
            'others' => '5'
        ];

        $typeCode = $typeMap[$engagementType] ?? '9';
        $currentYear = date('y'); // Get 2-digit year

        // Get the next sequence number for this type and year
        $lastProject = Project::where('job_id', 'like', "{$typeCode}-{$currentYear}-%")
            ->orderBy('job_id', 'desc')
            ->first();

        if ($lastProject) {
            $lastJobId = $lastProject->job_id;
            $parts = explode('-', $lastJobId);
            $lastSequence = intval($parts[2] ?? 0);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return sprintf('%s-%s-%03d', $typeCode, $currentYear, $nextSequence);
    }

    public function create(Request $request)
    {
        // Check permission
        if (!auth()->user()->canCreateProjects()) {
            abort(403, 'You do not have permission to create projects.');
        }

        // NEW: Handle pre-selected client from wireframe workflow
        $preselectedClientId = $request->get('client_id');
        $preselectedClient = null;

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
            'clients', 'staffByRole', 'preselectedClient'
        ));
    }

    // FIXED: Complete store method with proper validation and field mapping
    public function store(Request $request)
    {
        // Check permission
        if (!auth()->user()->canCreateProjects()) {
            abort(403, 'You do not have permission to create projects.');
        }

        // FIXED: Proper validation matching your form fields
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'engagement_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'engagement_type' => 'required|in:audit,accounting,tax,special_engagement,others',
            'engagement_period' => 'nullable|string|max:255',
            'status' => 'required|in:active,completed,on_hold,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',

            // Team assignments - matching your form field names
            'engagement_partner_id' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'associate_1' => 'nullable|exists:users,id',
            'associate_2' => 'nullable|exists:users,id',

            // Job ID should be generated, not required from form
            'job_id' => 'nullable|string|max:255',
        ]);

        // Verify user can access the selected client
        $client = Client::findOrFail($request->client_id);
        if (!$this->canAccessClient($client)) {
            abort(403, 'You do not have access to this client.');
        }

        DB::transaction(function () use ($validated, $request) {
            // Generate job ID if not provided
            if (empty($validated['job_id'])) {
                $validated['job_id'] = $this->generateJobId($validated['engagement_type']);
            }

            // Set created_by
            $validated['created_by'] = auth()->id();

            // FIXED: Map engagement_period to the correct database fields
            if ($validated['engagement_period']) {
                // You might want to parse this into start/end dates or store as text
                $validated['engagement_period_start'] = $validated['start_date'];
                $validated['engagement_period_end'] = $validated['end_date'];
            }

            // Create the project
            $project = Project::create([
                'name' => $validated['name'],
                'engagement_name' => $validated['engagement_name'],
                'description' => $validated['description'],
                'client_id' => $validated['client_id'],
                'engagement_type' => $validated['engagement_type'],
                'engagement_period' => $validated['engagement_period'],
                'engagement_period_start' => $validated['engagement_period_start'] ?? null,
                'engagement_period_end' => $validated['engagement_period_end'] ?? null,
                'status' => $validated['status'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'job_id' => $validated['job_id'],
                'created_by' => $validated['created_by'],
            ]);

            // FIXED: Handle team assignments using the assignment service
            $assignments = [
                'engagement_partner' => $validated['engagement_partner_id'] ?? null,
                'manager' => $validated['manager_id'] ?? null,
                'associate_1' => $validated['associate_1'] ?? null,
                'associate_2' => $validated['associate_2'] ?? null,
            ];

            // Only update team if we have assignments
            $hasAssignments = array_filter($assignments);
            if (!empty($hasAssignments)) {
                $this->assignmentService->updateProjectTeam($project, $assignments);
            }
        });

        return redirect()
            ->route('admin.projects.index')
            ->with('success', 'Project created successfully.');
    }
}
