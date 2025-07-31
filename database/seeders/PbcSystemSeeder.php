<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\PbcTemplate;
use App\Models\PbcTemplateItem;
use App\Models\PbcRequest;
use App\Models\PbcRequestItem;
use App\Services\PbcTemplateService;
use Illuminate\Support\Facades\Hash;

class PbcSystemSeeder extends Seeder
{
    public function run()
    {
        // Get existing users and clients
        $admin = User::where('email', 'admin@mtc.com')->first();
        $partner = User::where('email', 'partner@mtc.com')->first();
        $manager1 = User::where('email', 'manager1@mtc.com')->first();
        $manager2 = User::where('email', 'manager2@mtc.com')->first();
        $associate1 = User::where('email', 'associate1@mtc.com')->first();
        $associate2 = User::where('email', 'associate2@mtc.com')->first();

        $client1 = Client::whereHas('user', function($q) {
            $q->where('email', 'client1@abccorp.com');
        })->first();

        $client2 = Client::whereHas('user', function($q) {
            $q->where('email', 'client2@xyzcompany.com');
        })->first();

        $client3 = Client::whereHas('user', function($q) {
            $q->where('email', 'client3@defenterprises.com');
        })->first();

        // Create Sample Projects with Team Assignments
        $project1 = Project::updateOrCreate(
            ['name' => 'ABC Corp - Year-End Audit 2024'],
            [
                'name' => 'ABC Corp - Year-End Audit 2024',
                'description' => 'Annual financial statement audit for year 2024',
                'client_id' => $client1->id,
                'engagement_type' => Project::ENGAGEMENT_AUDIT,
                'engagement_period_start' => now()->subDays(30),
                'engagement_period_end' => now()->addDays(60),
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(60),
                'status' => Project::STATUS_ACTIVE,
                'created_by' => $admin->id,
            ]
        );

        $project2 = Project::updateOrCreate(
            ['name' => 'XYZ Inc - Tax Compliance Review'],
            [
                'name' => 'XYZ Inc - Tax Compliance Review',
                'description' => 'Comprehensive tax compliance review and documentation',
                'client_id' => $client2->id,
                'engagement_type' => Project::ENGAGEMENT_TAX,
                'engagement_period_start' => now()->subDays(15),
                'engagement_period_end' => now()->addDays(45),
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(45),
                'status' => Project::STATUS_ACTIVE,
                'created_by' => $admin->id,
            ]
        );

        $project3 = Project::updateOrCreate(
            ['name' => 'DEF Ltd - Accounting System Review'],
            [
                'name' => 'DEF Ltd - Accounting System Review',
                'description' => 'Review and improvement of accounting systems and processes',
                'client_id' => $client3->id,
                'engagement_type' => Project::ENGAGEMENT_ACCOUNTING,
                'engagement_period_start' => now()->subDays(10),
                'engagement_period_end' => now()->addDays(30),
                'start_date' => now()->subDays(10),
                'end_date' => now()->addDays(30),
                'status' => Project::STATUS_ACTIVE,
                'created_by' => $admin->id,
            ]
        );

        // Assign team members to projects
        $this->assignProjectTeam($project1, [
            'engagement_partner' => $partner->id,
            'manager' => $manager1->id,
            'associate_1' => $associate1->id,
        ]);

        $this->assignProjectTeam($project2, [
            'engagement_partner' => $partner->id,
            'manager' => $manager2->id,
            'associate_1' => $associate2->id,
        ]);

        $this->assignProjectTeam($project3, [
            'manager' => $manager1->id,
            'associate_1' => $associate1->id,
            'associate_2' => $associate2->id,
        ]);

        // Create Templates
        $this->createTemplates($admin);

        // Create sample PBC requests
        $this->createSamplePbcRequests($project1, $project2, $project3, $admin);

        echo "✅ Projects and assignments created successfully!\n";
        echo "📋 Projects created:\n";
        echo "   1. ABC Corp - Year-End Audit 2024 (Partner: John, Manager: Jane, Associate: Bob)\n";
        echo "   2. XYZ Inc - Tax Compliance Review (Partner: John, Manager: Mike, Associate: Alice)\n";
        echo "   3. DEF Ltd - Accounting System Review (Manager: Jane, Associates: Bob & Alice)\n";
        echo "📝 Templates and sample PBC requests created!\n";
    }

    private function assignProjectTeam(Project $project, array $assignments)
    {
        // Clear existing assignments
        ProjectAssignment::where('project_id', $project->id)->delete();

        // Create new assignments
        foreach ($assignments as $role => $userId) {
            if ($userId) {
                ProjectAssignment::create([
                    'project_id' => $project->id,
                    'user_id' => $userId,
                    'role' => $role,
                ]);
            }
        }
    }

    private function createTemplates(User $admin)
    {
        // Create Default Audit Template
        $template = PbcTemplate::updateOrCreate(
            ['name' => 'Standard Audit Requirements Checklist'],
            [
                'name' => 'Standard Audit Requirements Checklist',
                'description' => 'Comprehensive audit documentation checklist based on Philippine auditing standards',
                'header_info' => [
                    'engagement_partner' => '',
                    'engagement_manager' => '',
                    'document_date' => now()->toDateString(),
                ],
                'is_active' => true,
                'created_by' => $admin->id,
            ]
        );

        // Add template items
        if ($template->items()->count() === 0) {
            $defaultItems = $this->getDefaultAuditItems();
            foreach ($defaultItems as $item) {
                PbcTemplateItem::create([
                    'pbc_template_id' => $template->id,
                    'category' => $item['category'],
                    'particulars' => $item['particulars'],
                    'is_required' => $item['is_required'],
                    'order_index' => $item['order_index'],
                ]);
            }
        }

        // Create a simpler template for testing
        $simpleTemplate = PbcTemplate::updateOrCreate(
            ['name' => 'Basic Document Request'],
            [
                'name' => 'Basic Document Request',
                'description' => 'Simple document request template for quick testing',
                'header_info' => [
                    'engagement_partner' => 'Senior Partner',
                    'engagement_manager' => 'Audit Manager',
                    'document_date' => now()->toDateString(),
                ],
                'is_active' => true,
                'created_by' => $admin->id,
            ]
        );

        if ($simpleTemplate->items()->count() === 0) {
            $simpleItems = [
                ['category' => 'Financial', 'particulars' => 'Bank statements for the year', 'is_required' => true, 'order_index' => 1],
                ['category' => 'Financial', 'particulars' => 'Trial balance as of year-end', 'is_required' => true, 'order_index' => 2],
                ['category' => 'Legal', 'particulars' => 'Articles of incorporation', 'is_required' => true, 'order_index' => 3],
                ['category' => 'Tax', 'particulars' => 'Income tax returns', 'is_required' => true, 'order_index' => 4],
                ['category' => 'Tax', 'particulars' => 'VAT returns', 'is_required' => false, 'order_index' => 5],
            ];

            foreach ($simpleItems as $item) {
                PbcTemplateItem::create([
                    'pbc_template_id' => $simpleTemplate->id,
                    'category' => $item['category'],
                    'particulars' => $item['particulars'],
                    'is_required' => $item['is_required'],
                    'order_index' => $item['order_index'],
                ]);
            }
        }
    }

    private function createSamplePbcRequests(Project $project1, Project $project2, Project $project3, User $admin)
    {
        $simpleTemplate = PbcTemplate::where('name', 'Basic Document Request')->first();

        // Create PBC request for Project 1
        $pbcRequest1 = PbcRequest::updateOrCreate(
            ['title' => 'Q4 2024 Audit Documentation Request'],
            [
                'template_id' => $simpleTemplate->id,
                'client_id' => $project1->client_id,
                'project_id' => $project1->id,
                'title' => 'Q4 2024 Audit Documentation Request',
                'description' => 'Required documents for Q4 2024 audit engagement',
                'header_info' => [
                    'engagement_partner' => 'John Engagement Partner',
                    'engagement_manager' => 'Jane Manager',
                    'document_date' => now()->toDateString(),
                ],
                'due_date' => now()->addDays(14),
                'status' => 'pending',
                'created_by' => $admin->id,
            ]
        );

        // Create items for PBC request 1
        if ($pbcRequest1->items()->count() === 0) {
            $items = [
                ['category' => 'Financial', 'particulars' => 'Bank statements - all accounts (Jan-Dec 2024)', 'is_required' => true],
                ['category' => 'Financial', 'particulars' => 'Trial balance as of December 31, 2024', 'is_required' => true],
                ['category' => 'Financial', 'particulars' => 'Accounts receivable aging report', 'is_required' => true],
                ['category' => 'Inventory', 'particulars' => 'Physical inventory count sheets', 'is_required' => true],
                ['category' => 'Legal', 'particulars' => 'Board resolutions for 2024', 'is_required' => false],
            ];

            foreach ($items as $index => $item) {
                PbcRequestItem::create([
                    'pbc_request_id' => $pbcRequest1->id,
                    'category' => $item['category'],
                    'particulars' => $item['particulars'],
                    'is_required' => $item['is_required'],
                    'date_requested' => now()->toDateString(),
                    'order_index' => $index,
                    'status' => 'pending',
                ]);
            }
        }

        // Create PBC request for Project 2
        $pbcRequest2 = PbcRequest::updateOrCreate(
            ['title' => 'Tax Compliance Documentation'],
            [
                'template_id' => $simpleTemplate->id,
                'client_id' => $project2->client_id,
                'project_id' => $project2->id,
                'title' => 'Tax Compliance Documentation',
                'description' => 'Tax-related documents for compliance review',
                'header_info' => [
                    'engagement_partner' => 'John Engagement Partner',
                    'engagement_manager' => 'Mike Manager',
                    'document_date' => now()->toDateString(),
                ],
                'due_date' => now()->addDays(7),
                'status' => 'pending',
                'created_by' => $admin->id,
            ]
        );

        // Create items for PBC request 2
        if ($pbcRequest2->items()->count() === 0) {
            $items = [
                ['category' => 'Tax', 'particulars' => 'Annual Income Tax Return (ITR) 2024', 'is_required' => true],
                ['category' => 'Tax', 'particulars' => 'Monthly VAT returns (Jan-Dec 2024)', 'is_required' => true],
                ['category' => 'Tax', 'particulars' => 'Withholding tax returns - compensation', 'is_required' => true],
                ['category' => 'Tax', 'particulars' => 'Withholding tax returns - expanded', 'is_required' => true],
                ['category' => 'Payroll', 'particulars' => 'SSS, PhilHealth, PAG-IBIG remittances', 'is_required' => false],
            ];

            foreach ($items as $index => $item) {
                PbcRequestItem::create([
                    'pbc_request_id' => $pbcRequest2->id,
                    'category' => $item['category'],
                    'particulars' => $item['particulars'],
                    'is_required' => $item['is_required'],
                    'date_requested' => now()->toDateString(),
                    'order_index' => $index,
                    'status' => 'pending',
                ]);
            }
        }
    }

    private function getDefaultAuditItems()
    {
        return [
            ['category' => 'Financial Statements', 'particulars' => 'Trial Balance as of year-end', 'is_required' => true, 'order_index' => 1],
            ['category' => 'Financial Statements', 'particulars' => 'General Ledger for the year', 'is_required' => true, 'order_index' => 2],
            ['category' => 'Cash', 'particulars' => 'Bank statements for all accounts', 'is_required' => true, 'order_index' => 3],
            ['category' => 'Cash', 'particulars' => 'Bank reconciliations for year-end', 'is_required' => true, 'order_index' => 4],
            ['category' => 'Receivables', 'particulars' => 'Accounts receivable aging report', 'is_required' => true, 'order_index' => 5],
            ['category' => 'Receivables', 'particulars' => 'Bad debts write-off documentation', 'is_required' => false, 'order_index' => 6],
            ['category' => 'Inventory', 'particulars' => 'Physical inventory count sheets', 'is_required' => true, 'order_index' => 7],
            ['category' => 'Inventory', 'particulars' => 'Inventory valuation methods documentation', 'is_required' => true, 'order_index' => 8],
            ['category' => 'Fixed Assets', 'particulars' => 'Fixed asset register/schedule', 'is_required' => true, 'order_index' => 9],
            ['category' => 'Fixed Assets', 'particulars' => 'Depreciation schedules and methods', 'is_required' => true, 'order_index' => 10],
            ['category' => 'Payables', 'particulars' => 'Accounts payable aging report', 'is_required' => true, 'order_index' => 11],
            ['category' => 'Payables', 'particulars' => 'Accrued expenses documentation', 'is_required' => true, 'order_index' => 12],
            ['category' => 'Legal', 'particulars' => 'Articles of Incorporation and By-laws', 'is_required' => true, 'order_index' => 13],
            ['category' => 'Legal', 'particulars' => 'Board resolutions for the year', 'is_required' => true, 'order_index' => 14],
            ['category' => 'Tax', 'particulars' => 'Annual Income Tax Return', 'is_required' => true, 'order_index' => 15],
            ['category' => 'Tax', 'particulars' => 'Monthly VAT returns', 'is_required' => true, 'order_index' => 16],
            ['category' => 'Payroll', 'particulars' => 'Payroll registers and tax withholdings', 'is_required' => true, 'order_index' => 17],
            ['category' => 'Payroll', 'particulars' => 'SSS, PhilHealth, PAG-IBIG contributions', 'is_required' => true, 'order_index' => 18],
        ];
    }
}
