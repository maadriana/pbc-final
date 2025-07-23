<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\PbcTemplate;
use App\Models\PbcTemplateItem;
use App\Services\PbcTemplateService;
use Illuminate\Support\Facades\Hash;

class PbcSystemSeeder extends Seeder
{
    public function run()
    {
        // Create System Admin
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@pbcsystem.com',
            'password' => Hash::make('password'),
            'role' => 'system_admin',
        ]);

        // Create Sample Clients
        $clientUser1 = User::create([
            'name' => 'ABC Corporation',
            'email' => 'client1@abccorp.com',
            'password' => Hash::make('password'),
            'role' => 'client',
        ]);

        $client1 = Client::create([
            'user_id' => $clientUser1->id,
            'company_name' => 'ABC Corporation',
            'contact_person' => 'John Doe',
            'phone' => '+63-123-456-7890',
            'address' => 'Makati City, Metro Manila',
            'created_by' => $admin->id,
        ]);

        $clientUser2 = User::create([
            'name' => 'XYZ Company Inc.',
            'email' => 'client2@xyzcompany.com',
            'password' => Hash::make('password'),
            'role' => 'client',
        ]);

        $client2 = Client::create([
            'user_id' => $clientUser2->id,
            'company_name' => 'XYZ Company Inc.',
            'contact_person' => 'Jane Smith',
            'phone' => '+63-987-654-3210',
            'address' => 'Quezon City, Metro Manila',
            'created_by' => $admin->id,
        ]);

        // Create Sample Projects
        $project1 = Project::create([
            'name' => 'Year-End Audit 2024',
            'description' => 'Annual financial statement audit for year 2024',
            'start_date' => now()->subDays(30),
            'end_date' => now()->addDays(60),
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $project2 = Project::create([
            'name' => 'Tax Compliance Review',
            'description' => 'Comprehensive tax compliance review and documentation',
            'start_date' => now()->subDays(15),
            'end_date' => now()->addDays(45),
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        // Assign clients to projects
        $project1->clients()->attach($client1->id, [
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $project2->clients()->attach($client2->id, [
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        // Create Default Audit Template
        $template = PbcTemplate::create([
            'name' => 'Standard Audit Requirements Checklist',
            'description' => 'Comprehensive audit documentation checklist based on Philippine auditing standards',
            'header_info' => [
                'engagement_partner' => '',
                'engagement_manager' => '',
                'document_date' => now()->toDateString(),
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        // Add template items using the service
        $defaultItems = PbcTemplateService::getDefaultAuditItems();
        foreach ($defaultItems as $item) {
            PbcTemplateItem::create([
                'pbc_template_id' => $template->id,
                'category' => $item['category'],
                'particulars' => $item['particulars'],
                'is_required' => $item['is_required'],
                'order_index' => $item['order_index'],
            ]);
        }

        // Create a simpler template for testing
        $simpleTemplate = PbcTemplate::create([
            'name' => 'Basic Document Request',
            'description' => 'Simple document request template for quick testing',
            'header_info' => [
                'engagement_partner' => 'Senior Partner',
                'engagement_manager' => 'Audit Manager',
                'document_date' => now()->toDateString(),
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

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
