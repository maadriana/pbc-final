<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\PbcTemplate;
use App\Models\PbcTemplateItem;
use App\Models\PbcRequest;
use App\Models\PbcRequestItem;
use App\Services\JobGenerationService;
use Illuminate\Support\Facades\Hash;

class JobSampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds to match wireframe sample data
     */
    public function run(): void
    {
        // Create users to match wireframe counts (7 users total)
        $this->createUsers();

        // Create clients to match wireframe (2 clients)
        $this->createClients();

        // Create projects/jobs to match wireframe (3 projects)
        $this->createJobs();

        // Create PBC templates with CF/PF categories
        $this->createTemplates();

        // Create sample PBC requests (3 active requests)
        $this->createPbcRequests();
    }

    private function createUsers()
    {
        // 1. System Admin
        User::firstOrCreate(['email' => 'admin@mtc.com'], [
            'name' => 'System Administrator',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SYSTEM_ADMIN,
        ]);

        // 2-3. Engagement Partners
        User::firstOrCreate(['email' => 'ep1@mtc.com'], [
            'name' => 'John Engagement Partner',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ENGAGEMENT_PARTNER,
        ]);

        User::firstOrCreate(['email' => 'ep2@mtc.com'], [
            'name' => 'Jane Partner',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ENGAGEMENT_PARTNER,
        ]);

        // 4-5. Managers
        User::firstOrCreate(['email' => 'manager1@mtc.com'], [
            'name' => 'MNGR 1',
            'password' => Hash::make('password'),
            'role' => User::ROLE_MANAGER,
        ]);

        User::firstOrCreate(['email' => 'manager2@mtc.com'], [
            'name' => 'MNGR 2',
            'password' => Hash::make('password'),
            'role' => User::ROLE_MANAGER,
        ]);

        // 6-7. Associates
        User::firstOrCreate(['email' => 'staff1@mtc.com'], [
            'name' => 'STAFF 1',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ASSOCIATE,
        ]);

        User::firstOrCreate(['email' => 'staff2@mtc.com'], [
            'name' => 'STAFF 2',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ASSOCIATE,
        ]);
    }

    private function createClients()
    {
        $admin = User::where('role', User::ROLE_SYSTEM_ADMIN)->first();

        // Client 1: ABC Company (from wireframe)
        $clientUser1 = User::firstOrCreate(['email' => 'client1@abc.com'], [
            'name' => 'Juan Dela Cruz',
            'password' => Hash::make('password'),
            'role' => User::ROLE_CLIENT,
        ]);

        Client::firstOrCreate(['user_id' => $clientUser1->id], [
            'company_name' => 'ABC Company',
            'contact_person' => 'Juan Dela Cruz',
            'phone' => '0919-000-0000',
            'address' => '123 HV Dela Costa Salcedo Village Makati',
            'created_by' => $admin->id,
        ]);

        // Client 2: XYZ Corporation
        $clientUser2 = User::firstOrCreate(['email' => 'client2@xyz.com'], [
            'name' => 'Maria Santos',
            'password' => Hash::make('password'),
            'role' => User::ROLE_CLIENT,
        ]);

        Client::firstOrCreate(['user_id' => $clientUser2->id], [
            'company_name' => 'XYZ Corporation',
            'contact_person' => 'Maria Santos',
            'phone' => '0917-123-4567',
            'address' => '456 Business Ave, BGC, Taguig',
            'created_by' => $admin->id,
        ]);
    }

    private function createJobs()
    {
        $admin = User::where('role', User::ROLE_SYSTEM_ADMIN)->first();
        $ep1 = User::where('email', 'ep1@mtc.com')->first();
        $manager1 = User::where('email', 'manager1@mtc.com')->first();
        $abcCompany = Client::where('company_name', 'ABC Company')->first();
        $xyzCompany = Client::where('company_name', 'XYZ Corporation')->first();

        // Job 1: 1-01-001 - Statutory audit for YE122024 (from wireframe)
        Project::firstOrCreate(['job_id' => '1-01-001'], [
            'name' => 'ABC Company Audit 2024',
            'engagement_name' => 'Statutory audit for YE122024',
            'description' => 'Annual statutory audit for year ending December 31, 2024',
            'client_id' => $abcCompany->id,
            'engagement_type' => Project::ENGAGEMENT_AUDIT,
            'engagement_period_start' => '2024-01-01',
            'engagement_period_end' => '2024-12-31',
            'engagement_partner_id' => $ep1->id,
            'manager_id' => $manager1->id,
            'status' => Project::STATUS_ACTIVE,
            'start_date' => '2024-11-01',
            'end_date' => '2025-03-31',
            'created_by' => $admin->id,
        ]);

        // Job 2: 2-01-001 - Accounting Services
        Project::firstOrCreate(['job_id' => '2-01-001'], [
            'name' => 'XYZ Monthly Bookkeeping',
            'engagement_name' => 'Monthly accounting services',
            'description' => 'Monthly bookkeeping and financial statement preparation',
            'client_id' => $xyzCompany->id,
            'engagement_type' => Project::ENGAGEMENT_ACCOUNTING,
            'engagement_period_start' => '2024-01-01',
            'engagement_period_end' => '2024-12-31',
            'engagement_partner_id' => $ep1->id,
            'manager_id' => $manager1->id,
            'status' => Project::STATUS_ACTIVE,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'created_by' => $admin->id,
        ]);

        // Job 3: 3-01-001 - Tax Compliance
        Project::firstOrCreate(['job_id' => '3-01-001'], [
            'name' => 'ABC Tax Services 2024',
            'engagement_name' => 'Annual tax compliance',
            'description' => 'Preparation and filing of annual tax returns',
            'client_id' => $abcCompany->id,
            'engagement_type' => Project::ENGAGEMENT_TAX,
            'engagement_period_start' => '2024-01-01',
            'engagement_period_end' => '2024-12-31',
            'engagement_partner_id' => $ep1->id,
            'manager_id' => $manager1->id,
            'status' => Project::STATUS_ACTIVE,
            'start_date' => '2024-12-01',
            'end_date' => '2025-04-15',
            'created_by' => $admin->id,
        ]);
    }

    private function createTemplates()
    {
        $admin = User::where('role', User::ROLE_SYSTEM_ADMIN)->first();

        // Audit Template with CF/PF categories
        $auditTemplate = PbcTemplate::firstOrCreate(['name' => 'Standard Audit Template'], [
            'description' => 'Standard PBC template for audit engagements',
            'header_info' => [
                'engagement_partner' => 'EYM',
                'engagement_manager' => 'MNGR 1',
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        // Add template items with CF/PF categories
        $auditItems = [
            // Permanent File items
            ['category' => 'PF', 'particulars' => 'Latest Articles of Incorporation and By-laws', 'is_required' => true, 'order_index' => 1],
            ['category' => 'PF', 'particulars' => 'BIR Certificate of Registration', 'is_required' => true, 'order_index' => 2],
            ['category' => 'PF', 'particulars' => 'Latest General Information Sheet filed with the SEC', 'is_required' => true, 'order_index' => 3],
            ['category' => 'PF', 'particulars' => 'Stock transfer book', 'is_required' => true, 'order_index' => 4],

            // Current File items
            ['category' => 'CF', 'particulars' => 'Trial Balance as of December 31, 2024', 'is_required' => true, 'order_index' => 5],
            ['category' => 'CF', 'particulars' => 'General Ledger (all accounts)', 'is_required' => true, 'order_index' => 6],
            ['category' => 'CF', 'particulars' => 'Bank statements and reconciliations', 'is_required' => true, 'order_index' => 7],
            ['category' => 'CF', 'particulars' => 'Accounts receivable aging', 'is_required' => true, 'order_index' => 8],
        ];

        foreach ($auditItems as $item) {
            PbcTemplateItem::firstOrCreate([
                'pbc_template_id' => $auditTemplate->id,
                'particulars' => $item['particulars']
            ], $item);
        }
    }

    private function createPbcRequests()
    {
        $admin = User::where('role', User::ROLE_SYSTEM_ADMIN)->first();
        $abcCompany = Client::where('company_name', 'ABC Company')->first();
        $auditProject = Project::where('job_id', '1-01-001')->first();
        $auditTemplate = PbcTemplate::where('name', 'Standard Audit Template')->first();

        // Create PBC request matching wireframe
        $pbcRequest = PbcRequest::firstOrCreate([
            'title' => 'Statutory audit for YE122024',
            'client_id' => $abcCompany->id,
            'project_id' => $auditProject->id,
        ], [
            'template_id' => $auditTemplate->id,
            'description' => 'Please provide the following documents for the statutory audit',
            'header_info' => [
                'engagement_partner' => 'EYM',
                'manager' => 'MNGR 1',
                'staff' => ['STAFF 1', 'STAFF 2'],
            ],
            'status' => 'in_progress',
            'due_date' => '2025-01-31',
            'sent_at' => now()->subDays(10),
            'created_by' => $admin->id,
        ]);

        // Add request items matching wireframe categories
        $requestItems = [
            ['category' => 'CF', 'particulars' => 'Insert text', 'is_required' => true, 'order_index' => 1],
            ['category' => 'CF', 'particulars' => 'Insert text', 'is_required' => true, 'order_index' => 2],
            ['category' => 'PF', 'particulars' => 'Insert text', 'is_required' => true, 'order_index' => 3],
            ['category' => 'PF', 'particulars' => 'Insert text', 'is_required' => true, 'order_index' => 4],
        ];

        foreach ($requestItems as $item) {
            PbcRequestItem::firstOrCreate([
                'pbc_request_id' => $pbcRequest->id,
                'particulars' => $item['particulars'],
                'order_index' => $item['order_index']
            ], array_merge($item, [
                'date_requested' => '2025-07-25',
                'status' => 'pending'
            ]));
        }
    }
}
