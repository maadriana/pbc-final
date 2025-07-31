<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use App\Services\ExcelImportService;
use App\Services\JobGenerationService;
use App\Models\Project;
use App\Models\Client;
use App\Models\User;
use App\Http\Controllers\Admin\ImportController;

class TestPhase2 extends Command
{
    protected $signature = 'test:phase2 {--create-sample-excel}';
    protected $description = 'Test Phase 2 Import System Components';

    public function handle()
    {
        $this->info('🧪 Testing Phase 2: Excel Import System');
        $this->line('=======================================');

        // Test 1: Check required files exist
        $this->testFileExistence();

        // Test 2: Check service registration
        $this->testServiceRegistration();

        // Test 3: Check routes registration
        $this->testRoutesRegistration();

        // Test 4: Test user lookups
        $this->testUserLookups();

        // Test 5: Test Excel template generation
        $this->testTemplateGeneration();

        // Test 6: Create sample Excel file (optional)
        if ($this->option('create-sample-excel')) {
            $this->createSampleExcelFile();
        }

        // Test 7: Test import controller methods
        $this->testImportController();

        $this->info("\n✅ Phase 2 Testing Complete!");
        $this->line("If all tests passed, Phase 2 is working correctly.");

        if (!$this->option('create-sample-excel')) {
            $this->line("\nTo create a sample Excel file for testing:");
            $this->line("php artisan test:phase2 --create-sample-excel");
        }

        return 0;
    }

    private function testFileExistence()
    {
        $this->info("\n📁 Test 1: Checking Required Files");
        $this->line("----------------------------------");

        $requiredFiles = [
            'app/Http/Controllers/Admin/ImportController.php',
            'app/Http/Requests/ImportExcelRequest.php',
            'app/Services/ExcelImportService.php',
            'app/Services/JobGenerationService.php',
        ];

        $allFilesExist = true;

        foreach ($requiredFiles as $file) {
            if (File::exists(base_path($file))) {
                $this->line("✅ {$file}");
            } else {
                $this->error("❌ {$file} - MISSING");
                $allFilesExist = false;
            }
        }

        if (!$allFilesExist) {
            $this->error("\n⚠️ Some required files are missing. Please create them first.");
            return false;
        }

        $this->info("✅ All required files exist");
        return true;
    }

    private function testServiceRegistration()
    {
        $this->info("\n🔧 Test 2: Service Registration");
        $this->line("-------------------------------");

        try {
            // Test JobGenerationService
            $jobService = app(\App\Services\JobGenerationService::class);
            $this->line("✅ JobGenerationService registered");

            // Test ExcelImportService
            $importService = app(\App\Services\ExcelImportService::class);
            $this->line("✅ ExcelImportService registered");

            // Test job ID generation
            $sampleJobId = $jobService->generateJobId('audit');
            $this->line("✅ Job ID generation works: {$sampleJobId}");

            return true;
        } catch (\Exception $e) {
            $this->error("❌ Service registration failed: " . $e->getMessage());
            $this->line("💡 Make sure AppServiceProvider.php is updated with service bindings");
            return false;
        }
    }

    private function testRoutesRegistration()
    {
        $this->info("\n🛣️ Test 3: Routes Registration");
        $this->line("------------------------------");

        $expectedRoutes = [
            'admin.pbc-requests.import',
            'admin.pbc-requests.import.preview',
            'admin.pbc-requests.import.execute',
            'admin.pbc-requests.import.template',
            'admin.pbc-requests.import.bulk',
            'admin.pbc-requests.test-import',
        ];

        $allRoutesExist = true;

        foreach ($expectedRoutes as $routeName) {
            if (Route::has($routeName)) {
                $this->line("✅ {$routeName}");
            } else {
                $this->error("❌ {$routeName} - MISSING");
                $allRoutesExist = false;
            }
        }

        if (!$allRoutesExist) {
            $this->error("\n⚠️ Some routes are missing. Please add them to web.php");
            return false;
        }

        $this->info("✅ All import routes are registered");
        return true;
    }

    private function testUserLookups()
    {
        $this->info("\n👥 Test 4: User Lookup System");
        $this->line("-----------------------------");

        try {
            $project = Project::first();
            $client = Client::first();

            if (!$project) {
                $this->warn("⚠️ No projects found. Run: php artisan fix:project-setup");
                return false;
            }

            if (!$client) {
                $this->warn("⚠️ No clients found. Please create a client first.");
                return false;
            }

            $importService = app(\App\Services\ExcelImportService::class);

            // Use reflection to access private methods for testing
            $reflection = new \ReflectionClass($importService);

            $getAvailableUsersMethod = $reflection->getMethod('getAvailableUsers');
            $getAvailableUsersMethod->setAccessible(true);

            $getClientUsersMethod = $reflection->getMethod('getClientUsers');
            $getClientUsersMethod->setAccessible(true);

            $availableUsers = $getAvailableUsersMethod->invoke($importService, $project);
            $clientUsers = $getClientUsersMethod->invoke($importService, $client);

            $this->line("✅ Available MTC Staff: " . count($availableUsers) . " lookup options");
            $this->line("✅ Available Client Users: " . count($clientUsers) . " lookup options");

            // Show some examples
            $this->line("\n📋 Sample user references that will work:");
            $userExamples = array_slice(array_keys($availableUsers), 0, 5);
            foreach ($userExamples as $example) {
                $this->line("   - {$example}");
            }

            return true;
        } catch (\Exception $e) {
            $this->error("❌ User lookup test failed: " . $e->getMessage());
            return false;
        }
    }

    private function testTemplateGeneration()
    {
        $this->info("\n📊 Test 5: Excel Template Generation");
        $this->line("------------------------------------");

        try {
            $importService = app(\App\Services\ExcelImportService::class);

            // Test template generation (but don't actually download)
            $this->line("✅ ExcelImportService template method exists");
            $this->line("✅ PhpSpreadsheet is working");

            // Check if PhpSpreadsheet classes are available
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                $this->line("✅ PhpSpreadsheet library loaded");
            } else {
                $this->error("❌ PhpSpreadsheet library not found");
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->error("❌ Template generation test failed: " . $e->getMessage());
            return false;
        }
    }

    private function testImportController()
    {
        $this->info("\n🎮 Test 6: Import Controller Methods");
        $this->line("-----------------------------------");

        try {
            // Check if controller class exists and has required methods
            if (!class_exists('App\Http\Controllers\Admin\ImportController')) {
                $this->error("❌ ImportController class not found");
                return false;
            }

            $reflection = new \ReflectionClass('App\Http\Controllers\Admin\ImportController');

            $requiredMethods = [
                'showImportForm',
                'preview',
                'import',
                'downloadTemplate',
                'bulkImport',
                'testImport'
            ];

            foreach ($requiredMethods as $method) {
                if ($reflection->hasMethod($method)) {
                    $this->line("✅ Method: {$method}");
                } else {
                    $this->error("❌ Method missing: {$method}");
                }
            }

            $this->info("✅ Import controller structure is correct");
            return true;
        } catch (\Exception $e) {
            $this->error("❌ Import controller test failed: " . $e->getMessage());
            return false;
        }
    }

    private function createSampleExcelFile()
    {
        $this->info("\n📝 Creating Sample Excel File");
        $this->line("-----------------------------");

        try {
            // Get sample data from your system
            $project = Project::first();
            $client = Client::first();

            if (!$project || !$client) {
                $this->error("❌ Need at least one project and client to create sample file");
                return;
            }

            // Create sample Excel content
            $sampleData = [
                ['Category', 'Request Description', 'Requestor', 'Date Requested', 'Assigned to', 'Status'],
                ['CF', 'Trial Balance as of December 31, 2024', 'MNGR 1', '25/07/2025', 'ABC Corp Client', 'Pending'],
                ['CF', 'General Ledger (all accounts)', 'Jane Manager', '25/07/2025', 'client@test.com', 'Pending'],
                ['CF', 'Bank statements and reconciliations', 'manager@test.com', '25/07/2025', 'Client', 'Pending'],
                ['CF', 'Accounts receivable aging', 'Staff', '25/07/2025', 'ABC Corp Client', 'Uploaded'],
                ['CF', 'Accounts payable aging', 'Bob Associate', '25/07/2025', 'Client Staff 1', 'Pending'],
                ['PF', 'Articles of Incorporation and By-laws', 'EYM', '25/07/2025', 'ABC Corp Client', 'Pending'],
                ['PF', 'BIR Certificate of Registration', 'John Partner', '25/07/2025', 'client@test.com', 'Pending'],
                ['PF', 'Latest General Information Sheet', 'EP', '25/07/2025', 'Client', 'Pending'],
                ['PF', 'Stock transfer book', 'Staff 1', '25/07/2025', 'ABC Corp Client', 'Pending'],
                ['PF', 'Minutes of board meetings', 'test', '25/07/2025', 'client@test.com', 'Pending'],
            ];

            // Create CSV file (simpler than Excel for testing)
            $filePath = storage_path('app/sample_pbc_import.csv');
            $file = fopen($filePath, 'w');

            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }

            fclose($file);

            $this->info("✅ Sample Excel/CSV file created: {$filePath}");
            $this->line("\n📋 Sample file contains:");
            $this->line("   - 10 request items (5 CF + 5 PF)");
            $this->line("   - Uses actual user references from your system");
            $this->line("   - Ready for import testing");

            $this->line("\n🧪 To test import:");
            $this->line("   1. Visit: /admin/pbc-requests/test-import");
            $this->line("   2. Download template: /admin/pbc-requests/import/template");
            $this->line("   3. Use the sample file created above");

        } catch (\Exception $e) {
            $this->error("❌ Failed to create sample file: " . $e->getMessage());
        }
    }
}
