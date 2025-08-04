<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PbcRequest;
use App\Models\PbcRequestItem;
use App\Models\DocumentUpload;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class DebugRejectFunctionality extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'debug:reject-functionality {--client-id=1} {--project-id=5}';

    /**
     * The console command description.
     */
    protected $description = 'Debug the reject functionality for PBC requests';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Starting Reject Functionality Debug...');
        $this->newLine();

        $clientId = $this->option('client-id');
        $projectId = $this->option('project-id');

        // 1. Check Routes
        $this->checkRoutes();
        $this->newLine();

        // 2. Check Database Records
        $this->checkDatabaseRecords($clientId, $projectId);
        $this->newLine();

        // 3. Check Controller Methods
        $this->checkControllerMethods();
        $this->newLine();

        // 4. Test Route Resolution
        $this->testRouteResolution($clientId, $projectId);
        $this->newLine();

        // 5. Check Permissions
        $this->checkPermissions();
        $this->newLine();

        $this->info('✅ Debug completed! Check the results above.');
    }

    private function checkRoutes()
    {
        $this->info('🚀 1. Checking Routes...');

        $routes = collect(Route::getRoutes())->filter(function($route) {
            return str_contains($route->uri(), 'reject') &&
                   str_contains($route->uri(), 'pbc-requests');
        });

        if ($routes->isEmpty()) {
            $this->error('❌ No reject routes found!');
            return;
        }

        foreach ($routes as $route) {
            $this->line("✅ Route: {$route->methods()[0]} {$route->uri()}");
            $this->line("   Name: {$route->getName()}");
            $this->line("   Action: {$route->getActionName()}");
        }
    }

    private function checkDatabaseRecords($clientId, $projectId)
    {
        $this->info('🗄️  2. Checking Database Records...');

        // Check Client
        $client = Client::find($clientId);
        if (!$client) {
            $this->error("❌ Client with ID {$clientId} not found!");
            return;
        }
        $this->line("✅ Client found: {$client->company_name}");

        // Check Project
        $project = Project::find($projectId);
        if (!$project) {
            $this->error("❌ Project with ID {$projectId} not found!");
            return;
        }
        $this->line("✅ Project found: {$project->engagement_name}");

        // Check if project belongs to client
        if ($project->client_id != $clientId) {
            $this->error("❌ Project does not belong to client! Project client_id: {$project->client_id}");
            return;
        }
        $this->line("✅ Project belongs to client");

        // Check PBC Requests
        $requests = PbcRequest::where('client_id', $clientId)
            ->where('project_id', $projectId)
            ->with('items.documents')
            ->get();

        $this->line("✅ Found {$requests->count()} PBC requests for this project");

        foreach ($requests as $request) {
            $this->line("   Request ID: {$request->id} - {$request->title}");

            foreach ($request->items as $item) {
                $this->line("     Item ID: {$item->id} - Status: {$item->status}");

                $uploadedDocs = $item->documents->where('status', 'uploaded');
                $approvedDocs = $item->documents->where('status', 'approved');
                $rejectedDocs = $item->documents->where('status', 'rejected');

                $this->line("       Documents: {$uploadedDocs->count()} uploaded, {$approvedDocs->count()} approved, {$rejectedDocs->count()} rejected");

                if ($uploadedDocs->count() > 0) {
                    $this->line("       ✅ Has documents that can be rejected");
                }
            }
        }
    }

    private function checkControllerMethods()
    {
        $this->info('🎯 3. Checking Controller Methods...');

        $controller = new \App\Http\Controllers\Admin\PbcRequestController();

        // Check if methods exist
        if (method_exists($controller, 'rejectItem')) {
            $this->line('✅ rejectItem method exists');
        } else {
            $this->error('❌ rejectItem method not found!');
        }

        if (method_exists($controller, 'rejectItemGlobal')) {
            $this->line('✅ rejectItemGlobal method exists');
        } else {
            $this->error('❌ rejectItemGlobal method not found!');
        }

        // Check User model for permissions
        $user = User::where('role', 'system_admin')->first();
        if ($user) {
            if (method_exists($user, 'canReviewDocuments')) {
                $canReview = $user->canReviewDocuments();
                $this->line("✅ canReviewDocuments method exists - Result: " . ($canReview ? 'true' : 'false'));
            } else {
                $this->error('❌ canReviewDocuments method not found on User model!');
            }
        }
    }

    private function testRouteResolution($clientId, $projectId)
    {
        $this->info('🔗 4. Testing Route Resolution...');

        try {
            // Test global reject route
            $globalUrl = route('admin.pbc-requests.items.reject', ['item' => 1]);
            $this->line("✅ Global reject route: {$globalUrl}");
        } catch (\Exception $e) {
            $this->error("❌ Global reject route failed: {$e->getMessage()}");
        }

        try {
            // Test project-specific reject route
            $projectUrl = route('admin.clients.projects.pbc-requests.items.reject', [
                'client' => $clientId,
                'project' => $projectId,
                'request' => 1,
                'item' => 1
            ]);
            $this->line("✅ Project reject route: {$projectUrl}");
        } catch (\Exception $e) {
            $this->error("❌ Project reject route failed: {$e->getMessage()}");
        }
    }

    private function checkPermissions()
    {
        $this->info('🔐 5. Checking Permissions...');

        $users = User::all();
        foreach ($users as $user) {
            if (method_exists($user, 'canReviewDocuments')) {
                $canReview = $user->canReviewDocuments();
                $this->line("User {$user->name} ({$user->role}): canReviewDocuments = " . ($canReview ? 'true' : 'false'));
            }
        }
    }

    private function simulateRejectRequest($clientId, $projectId)
    {
        $this->info('🧪 6. Simulating Reject Request...');

        // Find a request with uploaded documents
        $request = PbcRequest::where('client_id', $clientId)
            ->where('project_id', $projectId)
            ->whereHas('items.documents', function($query) {
                $query->where('status', 'uploaded');
            })
            ->with('items.documents')
            ->first();

        if (!$request) {
            $this->error('❌ No request with uploaded documents found for testing');
            return;
        }

        $item = $request->items->first(function($item) {
            return $item->documents->where('status', 'uploaded')->count() > 0;
        });

        if (!$item) {
            $this->error('❌ No item with uploaded documents found');
            return;
        }

        $this->line("✅ Found test item: ID {$item->id}");
        $this->line("   Request ID: {$request->id}");
        $this->line("   Client ID: {$clientId}");
        $this->line("   Project ID: {$projectId}");

        // Show the exact URL that should be called
        $expectedUrl = "/admin/clients/{$clientId}/projects/{$projectId}/pbc-requests/{$request->id}/items/{$item->id}/reject";
        $this->line("✅ Expected reject URL: {$expectedUrl}");

        // Test the route
        try {
            $routeUrl = route('admin.clients.projects.pbc-requests.items.reject', [
                'client' => $clientId,
                'project' => $projectId,
                'request' => $request->id,
                'item' => $item->id
            ]);
            $this->line("✅ Route helper URL: {$routeUrl}");

            if ($expectedUrl === parse_url($routeUrl, PHP_URL_PATH)) {
                $this->line("✅ URLs match!");
            } else {
                $this->error("❌ URL mismatch!");
            }
        } catch (\Exception $e) {
            $this->error("❌ Route generation failed: {$e->getMessage()}");
        }
    }
}
