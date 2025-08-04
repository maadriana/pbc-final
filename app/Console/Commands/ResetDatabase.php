<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class ResetDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:reset-with-admin {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset database and create system admin user only';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Confirmation prompt
        if (!$this->option('force')) {
            if (!$this->confirm('This will delete ALL data and create only a system admin. Continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting database reset...');

        try {
            // Step 1: Disable foreign key checks
            $this->info('Disabling foreign key checks...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Step 2: Get all table names
            $tables = DB::select('SHOW TABLES');
            $databaseName = DB::getDatabaseName();
            $tableKey = "Tables_in_{$databaseName}";

            // Step 3: Truncate all tables except migrations
            $this->info('Truncating all tables...');
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;

                // Skip system tables
                if (!in_array($tableName, ['migrations', 'failed_jobs'])) {
                    DB::table($tableName)->truncate();
                    $this->line("  - Truncated: {$tableName}");
                }
            }

            // Step 4: Re-enable foreign key checks
            $this->info('Re-enabling foreign key checks...');
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Step 5: Create system admin user
            $this->info('Creating system admin user...');
            $admin = User::create([
                'name' => 'System Administrator',
                'email' => 'admin@pbc-portal.com',
                'password' => Hash::make('admin123'),
                'role' => User::ROLE_SYSTEM_ADMIN,
                'email_verified_at' => now(),
            ]);

            // Step 6: Display success information
            $this->newLine();
            $this->info('✅ Database reset completed successfully!');
            $this->newLine();

            $this->comment('📋 System Admin Account Created:');
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $admin->id],
                    ['Name', $admin->name],
                    ['Email', $admin->email],
                    ['Password', 'admin123'],
                    ['Role', $admin->getRoleDisplayName()],
                    ['Status', 'Verified & Active'],
                ]
            );

            $this->newLine();
            $this->comment('🚀 You can now login with:');
            $this->line("   Email: {$admin->email}");
            $this->line("   Password: admin123");
            $this->newLine();

            $this->warn('⚠️  Remember to change the password after first login!');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error during database reset: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());

            // Try to re-enable foreign key checks in case of error
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Exception $fkError) {
                $this->error('Could not re-enable foreign key checks: ' . $fkError->getMessage());
            }

            return 1;
        }
    }
}
