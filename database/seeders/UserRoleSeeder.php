<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create MTC Staff Users
        $admin = User::updateOrCreate(
            ['email' => 'admin@mtc.com'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@mtc.com',
                'role' => User::ROLE_SYSTEM_ADMIN,
                'password' => Hash::make('password'),
            ]
        );

        $partner = User::updateOrCreate(
            ['email' => 'partner@mtc.com'],
            [
                'name' => 'John Engagement Partner',
                'email' => 'partner@mtc.com',
                'role' => User::ROLE_ENGAGEMENT_PARTNER,
                'password' => Hash::make('password'),
            ]
        );

        $manager1 = User::updateOrCreate(
            ['email' => 'manager1@mtc.com'],
            [
                'name' => 'Jane Manager',
                'email' => 'manager1@mtc.com',
                'role' => User::ROLE_MANAGER,
                'password' => Hash::make('password'),
            ]
        );

        $manager2 = User::updateOrCreate(
            ['email' => 'manager2@mtc.com'],
            [
                'name' => 'Mike Manager',
                'email' => 'manager2@mtc.com',
                'role' => User::ROLE_MANAGER,
                'password' => Hash::make('password'),
            ]
        );

        $associate1 = User::updateOrCreate(
            ['email' => 'associate1@mtc.com'],
            [
                'name' => 'Bob Associate',
                'email' => 'associate1@mtc.com',
                'role' => User::ROLE_ASSOCIATE,
                'password' => Hash::make('password'),
            ]
        );

        $associate2 = User::updateOrCreate(
            ['email' => 'associate2@mtc.com'],
            [
                'name' => 'Alice Associate',
                'email' => 'associate2@mtc.com',
                'role' => User::ROLE_ASSOCIATE,
                'password' => Hash::make('password'),
            ]
        );

        // Create Client Users
        $clientUser1 = User::updateOrCreate(
            ['email' => 'client1@abccorp.com'],
            [
                'name' => 'ABC Corporation User',
                'email' => 'client1@abccorp.com',
                'role' => User::ROLE_CLIENT,
                'password' => Hash::make('password'),
            ]
        );

        $clientUser2 = User::updateOrCreate(
            ['email' => 'client2@xyzcompany.com'],
            [
                'name' => 'XYZ Company User',
                'email' => 'client2@xyzcompany.com',
                'role' => User::ROLE_CLIENT,
                'password' => Hash::make('password'),
            ]
        );

        $clientUser3 = User::updateOrCreate(
            ['email' => 'client3@defenterprises.com'],
            [
                'name' => 'DEF Enterprises User',
                'email' => 'client3@defenterprises.com',
                'role' => User::ROLE_CLIENT,
                'password' => Hash::make('password'),
            ]
        );

        // Create Client records
        Client::updateOrCreate(
            ['user_id' => $clientUser1->id],
            [
                'user_id' => $clientUser1->id,
                'company_name' => 'ABC Corporation',
                'contact_person' => 'John Doe',
                'phone' => '+63-123-456-7890',
                'address' => 'Makati City, Metro Manila',
                'created_by' => $admin->id,
            ]
        );

        Client::updateOrCreate(
            ['user_id' => $clientUser2->id],
            [
                'user_id' => $clientUser2->id,
                'company_name' => 'XYZ Company Inc.',
                'contact_person' => 'Jane Smith',
                'phone' => '+63-987-654-3210',
                'address' => 'Quezon City, Metro Manila',
                'created_by' => $admin->id,
            ]
        );

        Client::updateOrCreate(
            ['user_id' => $clientUser3->id],
            [
                'user_id' => $clientUser3->id,
                'company_name' => 'DEF Enterprises Ltd.',
                'contact_person' => 'Robert Johnson',
                'phone' => '+63-555-123-4567',
                'address' => 'Pasig City, Metro Manila',
                'created_by' => $admin->id,
            ]
        );

        echo "✅ Users and Clients created successfully!\n";
        echo "🔑 Login Credentials:\n";
        echo "   Admin: admin@mtc.com / password\n";
        echo "   Partner: partner@mtc.com / password\n";
        echo "   Manager 1: manager1@mtc.com / password\n";
        echo "   Manager 2: manager2@mtc.com / password\n";
        echo "   Associate 1: associate1@mtc.com / password\n";
        echo "   Associate 2: associate2@mtc.com / password\n";
        echo "   Client 1: client1@abccorp.com / password\n";
        echo "   Client 2: client2@xyzcompany.com / password\n";
        echo "   Client 3: client3@defenterprises.com / password\n";
    }
}
