<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\PbcTemplate;
use App\Models\PbcTemplateItem;
use App\Models\PbcRequest;
use App\Models\PbcRequestItem;
use App\Services\JobGenerationService;
use Illuminate\Support\Facades\Hash;

class TestPhase1 extends Command
{
    protected $signature = 'test:phase1';
    protected $description = 'Test Phase 1 wireframe alignment features';

    public function handle()
    {
        $this->info('🧪 Testing Phase 1: Core Structure Updates');
        $this->line('');

        // Test 1: Job Generation Service
        $this->testJobGenerationService();

        // Test 2: Category System
        $this->testCategorySystem();

        // Test 3: Project/Job Creation
        $this->testProjectJobCreation();

        // Test 4: Dashboard Metrics
        $this->testDashboardMetrics();

        // Test 5: Database Structure
        $this->testDatabaseStructure();

        $this->line('');
        $this->info('✅ Phase 1 Testing Complete!');

        return 0;
    }

    private function testJobGenerationService()
    {
        $this->line('=== TEST 1: Job Generation Service ===');

        try {
            $jobService = app(JobGenerationService::class);

            // Test different engagement types
            $auditJob = $jobService->generateJobId('audit');
            $accountingJob = $jobService->generateJobId('accounting');
            $taxJob = $jobService->generateJobId('tax');

            $this->info("✅ Audit Job ID: {$auditJob}");
            $this->info("✅ Accounting Job ID: {$accountingJob}");
            $this->info("✅ Tax Job ID: {$taxJob}");

            // Test job ID validation
            $isValid = $jobService->isValidJobId($auditJob);
            $this->info("✅ Job ID validation: " . ($isValid ? 'PASS' : 'FAIL'));

            // Test job ID parsing
            $parts = $jobService->parseJobId($auditJob);
            $this->info("✅ Job ID parsed: " . json_encode($parts));

        } catch (\Exception $e) {
            $this->error("❌ Job Generation Service Error: " . $e->getMessage());
        }

        $this->line('');
    }

    private function testCategorySystem()
    {
        $this->line('=== TEST 2: Category System (CF/PF) ===');

        try {
            // Test template item categories
            $categories = PbcTemplateItem::getCategories();
            $this->info("✅ Available categories: " . implode(', ', array_keys($categories)));

            // Create test template item
            $testTemplate = PbcTemplate::first();
            if (!$testTemplate) {
                $testTemplate = PbcTemplate::create([
                    'name' => 'Test Template',
                    'description' => 'Test template for Phase 1',
                    'is_active' => true,
                    'created_by' => 1
                ]);
            }

            // Test CF category
            $cfItem = PbcTemplateItem::create([
                'pbc_template_id' => $testTemplate->id,
                'category' => PbcTemplateItem::CATEGORY_CURRENT_FILE,
                'particulars' => 'Test Current File Document',
                'is_required' => true,
                'order_index' => 1
            ]);

            $this->info("✅ CF Item created - Display: " . $cfItem->category_display);
            $this->info("✅ CF Item color class: " . $cfItem->getCategoryColorClass());

            // Test PF category
            $pfItem = PbcTemplateItem::create([
                'pbc_template_id' => $testTemplate->id,
                'category' => PbcTemplateItem::CATEGORY_PERMANENT_FILE,
                'particulars' => 'Test Permanent File Document',
                'is_required' => true,
                'order_index' => 2
            ]);

            $this->info("✅ PF Item created - Display: " . $pfItem->category_display);
            $this->info("✅ PF Item color class: " . $pfItem->getCategoryColorClass());

            // Test scopes
            $cfCount = PbcTemplateItem::currentFile()->count();
            $pfCount = PbcTemplateItem::permanentFile()->count();
            $this->info("✅ CF items count: {$cfCount}");
            $this->info("✅ PF items count: {$pfCount}");

        } catch (\Exception $e) {
            $this->error("❌ Category System Error: " . $e->getMessage());
        }

        $this->line('');
    }

    private function testProjectJobCreation()
    {
        $this->line('=== TEST 3: Project/Job Creation ===');

        try {
            // Ensure we have required data
            $admin = User::where('role', User::ROLE_SYSTEM_ADMIN)->first();
            if (!$admin) {
                $admin = User::create([
                    'name' => 'Test Admin',
                    'email' => 'testadmin@test.com',
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_SYSTEM_ADMIN
                ]);
            }

            $client = Client::first();
            if (!$client) {
                $clientUser = User::create([
                    'name' => 'Test Client',
                    'email' => 'testclient@test.com',
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_CLIENT
                ]);

                $client = Client::create([
                    'user_id' => $clientUser->id,
                    'company_name' => 'Test Company Ltd',
                    'contact_person' => 'Test Contact',
                    'created_by' => $admin->id
                ]);
            }

            // Test auto job_id generation
            $project = Project::create([
                'name' => 'Test Audit Project',
                'engagement_name' => 'Annual Audit 2025',
                'description' => 'Test project for Phase 1',
                'client_id' => $client->id,
                'engagement_type' => 'audit',
                'engagement_partner_id' => $admin->id,
                'manager_id' => $admin->id,
                'status' => 'active',
                'created_by' => $admin->id
            ]);

            $this->info("✅ Project created with auto job_id: {$project->job_id}");
            $this->info("✅ Job display name: " . $project->getJobDisplayName());
            $this->info("✅ Engagement year: " . $project->getEngagementYear());
            $this->info("✅ Sequence number: " . $project->getSequenceNumber());

            // Test relationships
            $this->info("✅ Engagement Partner: " . ($project->engagementPartner ? $project->engagementPartner->name : 'None'));
            $this->info("✅ Manager: " . ($project->manager ? $project->manager->name : 'None'));
            $this->info("✅ Client: " . ($project->client ? $project->client->company_name : 'None'));

        } catch (\Exception $e) {
            $this->error("❌ Project Creation Error: " . $e->getMessage());
        }

        $this->line('');
    }

    private function testDashboardMetrics()
    {
        $this->line('=== TEST 4: Dashboard Metrics ===');

        try {
            // Count current data
            $userCount = User::count();
            $clientCount = Client::count();
            $projectCount = Project::count();
            $activeRequestCount = PbcRequest::whereIn('status', ['pending', 'in_progress'])->count();

            $this->info("✅ Total Users: {$userCount}");
            $this->info("✅ Total Clients: {$clientCount}");
            $this->info("✅ Total Projects: {$projectCount}");
            $this->info("✅ Active Requests: {$activeRequestCount}");

            // Test dashboard controller method
            $controller = new \App\Http\Controllers\Admin\DashboardController();
            $this->info("✅ Dashboard controller accessible");

        } catch (\Exception $e) {
            $this->error("❌ Dashboard Metrics Error: " . $e->getMessage());
        }

        $this->line('');
    }

    private function testDatabaseStructure()
    {
        $this->line('=== TEST 5: Database Structure ===');

        try {
            // Check projects table structure
            $this->checkTableColumn('projects', 'job_id', 'varchar(20)');
            $this->checkTableColumn('projects', 'engagement_name', 'varchar(255)');
            $this->checkTableColumn('projects', 'engagement_partner_id', 'bigint');
            $this->checkTableColumn('projects', 'manager_id', 'bigint');

            // Check category columns
            $this->checkTableColumn('pbc_request_items', 'category', 'enum');
            $this->checkTableColumn('pbc_template_items', 'category', 'enum');

            $this->info("✅ Database structure validation complete");

        } catch (\Exception $e) {
            $this->error("❌ Database Structure Error: " . $e->getMessage());
        }

        $this->line('');
    }

    private function checkTableColumn($table, $column, $expectedType)
    {
        $columns = \DB::select("DESCRIBE {$table}");
        $found = false;

        foreach ($columns as $col) {
            if ($col->Field === $column) {
                $found = true;
                $typeMatches = str_contains(strtolower($col->Type), strtolower($expectedType));

                if ($typeMatches) {
                    $this->info("✅ {$table}.{$column}: {$col->Type}");
                } else {
                    $this->warn("⚠️  {$table}.{$column}: {$col->Type} (expected {$expectedType})");
                }
                break;
            }
        }

        if (!$found) {
            $this->error("❌ {$table}.{$column}: NOT FOUND");
        }
    }
}
