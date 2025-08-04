<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if year_engaged column already exists
        if (!Schema::hasColumn('clients', 'year_engaged')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->year('year_engaged')->nullable()->after('company_name');
            });
        }

        // Populate year_engaged for existing clients
        $this->populateYearEngaged();
    }

    public function down(): void
    {
        if (Schema::hasColumn('clients', 'year_engaged')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('year_engaged');
            });
        }
    }

    private function populateYearEngaged(): void
    {
        $clients = DB::table('clients')->whereNull('year_engaged')->get();

        foreach ($clients as $client) {
            $firstProject = DB::table('projects')
                ->where('client_id', $client->id)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($firstProject) {
                $yearEngaged = date('Y', strtotime($firstProject->created_at));
            } else {
                $yearEngaged = date('Y', strtotime($client->created_at));
            }

            DB::table('clients')
                ->where('id', $client->id)
                ->update(['year_engaged' => $yearEngaged]);
        }
    }
};
