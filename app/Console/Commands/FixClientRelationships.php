<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Client;

class FixClientRelationships extends Command
{
    protected $signature = 'fix:client-relationships';
    protected $description = 'Create client records for users who dont have them';

    public function handle()
    {
        $users = User::where('role', 'client')->whereDoesntHave('client')->get();

        if ($users->isEmpty()) {
            $this->info('All client users already have client records.');
            return;
        }

        $this->info("Found {$users->count()} users without client records.");

        foreach($users as $user) {
            Client::create([
                'user_id' => $user->id,
                'company_name' => $user->name . ' Company',
                'contact_person' => $user->name,
                'created_by' => 1, // Default admin user
            ]);

            $this->line("Created client record for: {$user->email}");
        }

        $this->info('Client relationships fixed successfully!');
    }
}
