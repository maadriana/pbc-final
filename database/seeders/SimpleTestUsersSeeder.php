<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectAssignment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SimpleTestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProjectAssignment::truncate();
        Project::truncate();
        Client::truncate();
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. SYSTEM ADMIN
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'system_admin',
        ]);

        // 2. ENGAGEMENT PARTNER
        $partner = User::create([
            'name' => 'John Partner',
            'email' => 'partner@test.com',
            'password' => Hash::make('password'),
            'role' => 'engagement_partner',
        ]);

        // 3. MANAGER
        $manager = User::create([
            'name' => 'Jane Manager',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);

        // 4. ASSOCIATE
        $associate = User::create([
            'name' => 'Bob Associate',
            'email' => 'associate@test.com',
            'password' => Hash::make('password'),
            'role' => 'associate',
        ]);

        // 5. CLIENT USER
        $clientUser = User::create([
            'name' => 'ABC Corp Client',
            'email' => 'client@test.com',
            'password' => Hash::make('password'),
            'role' => 'client',
        ]);

        // Create Client Company for the client user
        $client = Client::create([
            'user_id' => $clientUser->id,
            'company_name' => 'ABC Corporation',
            'contact_person' => 'John Doe',
            'phone' => '+63-123-456-7890',
            'address' => 'Makati City, Metro Manila',
            'created_by' => $admin->id,
        ]);

        // Create a Test Project
        $project = Project::create([
            'name' => 'ABC Corp - Test Audit 2024',
            'description' => 'Test project for role-based access control',
            'client_id' => $client->id,
            'engagement_type' => 'audit',
            'engagement_period_start' => now()->subDays(30),
            'engagement_period_end' => now()->addDays(60),
            'start_date' => now()->subDays(30),
            'end_date' => now()->addDays(60),
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        // Assign team members to the project (NOT admin - admin has access to all)
        ProjectAssignment::create([
            'project_id' => $project->id,
            'user_id' => $partner->id,
            'role' => 'engagement_partner',
        ]);

        ProjectAssignment::create([
            'project_id' => $project->id,
            'user_id' => $manager->id,
            'role' => 'manager',
        ]);

        ProjectAssignment::create([
            'project_id' => $project->id,
            'user_id' => $associate->id,
            'role' => 'associate_1',
        ]);

        // Note: Client user gets access through client_id relationship, not project assignments

        echo "\n✅ 5 Test Users Created Successfully!\n";
        echo "🔑 Login Credentials for Testing:\n";
        echo "   1. System Admin: admin@test.com / password (Access: ALL projects)\n";
        echo "   2. Engagement Partner: partner@test.com / password (Access: assigned projects)\n";
        echo "   3. Manager: manager@test.com / password (Access: assigned projects)\n";
        echo "   4. Associate: associate@test.com / password (Access: assigned projects)\n";
        echo "   5. Client: client@test.com / password (Access: only own company projects)\n";
        echo "\n📋 Test Project Created: 'ABC Corp - Test Audit 2024'\n";
        echo "👥 Project Team: Partner, Manager, Associate (Admin has system-wide access)\n";
        echo "🏢 Client Company: ABC Corporation\n\n";

        echo "🧪 TEST SCENARIOS:\n";
        echo "   - Admin can see ALL projects and clients\n";
        echo "   - Partner/Manager/Associate can only see 'ABC Corp - Test Audit 2024'\n";
        echo "   - Client can only see their company's project\n";
        echo "   - Try creating PBC requests with different users\n";
        echo "   - Test dashboard access with each role\n\n";
    }
}
