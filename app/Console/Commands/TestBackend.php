<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\PbcTemplate;
use App\Models\PbcTemplateItem;

class TestBackend extends Command
{
    protected $signature = 'test:backend';
    protected $description = 'Test all backend functionality quickly';

    public function handle()
    {
        $this->info('Testing PBC System Backend...');

        // Test 1: Create Admin User
        $this->line('1. Creating admin user...');
        $admin = User::firstOrCreate([
            'email' => 'admin@test.com'
        ], [
            'name' => 'Test Admin',
            'password' => bcrypt('password'),
            'role' => 'system_admin'
        ]);
        $this->info("   Admin created: {$admin->email}");

        // Test 2: Create Client
        $this->line('2. Creating client...');
        $clientUser = User::firstOrCreate([
            'email' => 'client@test.com'
        ], [
            'name' => 'Test Client User',
            'password' => bcrypt('password'),
            'role' => 'client'
        ]);

        $client = Client::firstOrCreate([
            'user_id' => $clientUser->id
        ], [
            'company_name' => 'Test Company Inc.',
            'contact_person' => 'John Doe',
            'phone' => '+63-123-456-7890',
            'address' => 'Test Address, Metro Manila',
            'created_by' => $admin->id,
        ]);
        $this->info("   Client created: {$client->company_name}");

        // Test 3: Create Project
        $this->line('3. Creating project...');
        $project = Project::firstOrCreate([
            'name' => 'Test Audit 2024'
        ], [
            'description' => 'Test audit project for backend testing',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $this->info("   Project created: {$project->name}");

        // Test 4: Assign Client to Project
        if (!$project->clients()->where('client_id', $client->id)->exists()) {
            $project->clients()->attach($client->id, [
                'assigned_by' => $admin->id,
                'assigned_at' => now(),
            ]);
            $this->info('   Client assigned to project');
        }

        // Test 5: Create Template
        $this->line('4. Creating template...');
        $template = PbcTemplate::firstOrCreate([
            'name' => 'Basic Test Template'
        ], [
            'description' => 'Simple template for backend testing',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        // Add template items if not exist
        if ($template->templateItems()->count() === 0) {
            PbcTemplateItem::create([
                'pbc_template_id' => $template->id,
                'category' => 'Financial',
                'particulars' => 'Bank statements for the year',
                'is_required' => true,
                'order_index' => 1,
            ]);

            PbcTemplateItem::create([
                'pbc_template_id' => $template->id,
                'category' => 'Financial',
                'particulars' => 'Trial balance as of year-end',
                'is_required' => true,
                'order_index' => 2,
            ]);
        }
        $this->info("   Template created with items: {$template->name}");

        // Test 6: Show Summary
        $this->line('5. Backend Test Summary:');
        $this->table([
            ['Model', 'Count'],
        ], [
            ['Users', User::count()],
            ['Clients', Client::count()],
            ['Projects', Project::count()],
            ['Templates', PbcTemplate::count()],
            ['Template Items', PbcTemplateItem::count()],
        ]);

        $this->info('Backend testing completed successfully!');
        $this->line('Login credentials:');
        $this->line("Admin: admin@test.com / password");
        $this->line("Client: client@test.com / password");

        return 0;
    }
}
