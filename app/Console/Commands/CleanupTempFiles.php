<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DocumentUpload;
use Illuminate\Support\Facades\Storage;

class CleanupTempFiles extends Command
{
    protected $signature = 'pbc:cleanup-temp-files';
    protected $description = 'Clean up temporary and orphaned files from storage';

    public function handle()
    {
        $this->info('Starting cleanup of temporary files...');

        // Find orphaned files (files in storage but not in database)
        $allFiles = Storage::disk('local')->allFiles('pbc-documents');
        $databaseFiles = DocumentUpload::pluck('file_path')->toArray();

        $orphanedFiles = array_diff($allFiles, $databaseFiles);
        $cleanedCount = 0;

        foreach ($orphanedFiles as $file) {
            // Only delete files older than 7 days
            $fileTime = Storage::disk('local')->lastModified($file);
            if ($fileTime < now()->subDays(7)->timestamp) {
                Storage::disk('local')->delete($file);
                $cleanedCount++;
                $this->line("Deleted orphaned file: {$file}");
            }
        }

        $this->info("Cleanup completed. Removed {$cleanedCount} orphaned files.");
        return 0;
    }
}
