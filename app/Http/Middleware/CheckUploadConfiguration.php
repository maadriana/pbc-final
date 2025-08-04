<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckUploadConfiguration
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only run on admin routes that handle file uploads
        if ($request->is('admin/pbc-requests*') || $request->is('admin/documents*')) {
            $this->validateConfiguration();
        }

        return $next($request);
    }

    /**
     * Validate PHP configuration for large file uploads
     */
    private function validateConfiguration()
    {
        $warnings = [];

        // Check critical settings
        $uploadMaxFilesize = $this->parseSize(ini_get('upload_max_filesize'));
        $postMaxSize = $this->parseSize(ini_get('post_max_size'));
        $memoryLimit = $this->parseSize(ini_get('memory_limit'));
        $maxExecutionTime = ini_get('max_execution_time');
        $maxInputTime = ini_get('max_input_time');

        if ($uploadMaxFilesize < 314572800) { // 300MB
            $warnings[] = 'upload_max_filesize too low: ' . ini_get('upload_max_filesize') . ' (recommended: 300M)';
        }

        if ($postMaxSize < 314572800) { // 300MB
            $warnings[] = 'post_max_size too low: ' . ini_get('post_max_size') . ' (recommended: 300M)';
        }

        if ($memoryLimit > 0 && $memoryLimit < 536870912) { // 512MB
            $warnings[] = 'memory_limit too low: ' . ini_get('memory_limit') . ' (recommended: 512M)';
        }

        if ($maxExecutionTime > 0 && $maxExecutionTime < 600) { // 10 minutes
            $warnings[] = 'max_execution_time too low: ' . $maxExecutionTime . 's (recommended: 600s)';
        }

        if ($maxInputTime > 0 && $maxInputTime < 600) { // 10 minutes
            $warnings[] = 'max_input_time too low: ' . $maxInputTime . 's (recommended: 600s)';
        }

        // Check disk space
        $diskFreeBytes = disk_free_space(storage_path('app'));
        $diskTotalBytes = disk_total_space(storage_path('app'));

        if ($diskFreeBytes !== false && $diskTotalBytes !== false) {
            $diskUsagePercentage = (($diskTotalBytes - $diskFreeBytes) / $diskTotalBytes) * 100;

            if ($diskUsagePercentage > 90) {
                $warnings[] = 'Low disk space: ' . number_format($diskUsagePercentage, 1) . '% used';
            }

            if ($diskFreeBytes < 1073741824) { // Less than 1GB free
                $warnings[] = 'Very low disk space: only ' . $this->formatBytes($diskFreeBytes) . ' free';
            }
        }

        // Check if storage directory is writable
        if (!is_writable(storage_path('app'))) {
            $warnings[] = 'Storage directory is not writable: ' . storage_path('app');
        }

        // Check if pbc-documents directory exists and is writable
        $pbcDocumentsPath = storage_path('app/pbc-documents');
        if (!file_exists($pbcDocumentsPath)) {
            try {
                mkdir($pbcDocumentsPath, 0755, true);
            } catch (\Exception $e) {
                $warnings[] = 'Cannot create pbc-documents directory: ' . $e->getMessage();
            }
        } elseif (!is_writable($pbcDocumentsPath)) {
            $warnings[] = 'PBC documents directory is not writable: ' . $pbcDocumentsPath;
        }

        if (!empty($warnings)) {
            Log::warning('PHP configuration not optimal for large file uploads', [
                'warnings' => $warnings,
                'current_settings' => [
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'max_input_time' => ini_get('max_input_time')
                ],
                'disk_info' => [
                    'free_space' => $this->formatBytes($diskFreeBytes ?: 0),
                    'total_space' => $this->formatBytes($diskTotalBytes ?: 0),
                    'usage_percentage' => isset($diskUsagePercentage) ? number_format($diskUsagePercentage, 1) . '%' : 'unknown'
                ]
            ]);

            // Store warnings in session for display (only for system admins)
            if (auth()->check() && auth()->user()->isSystemAdmin()) {
                session()->flash('upload_config_warnings', $warnings);
            }
        }
    }

    /**
     * Parse size string to bytes
     */
    private function parseSize($size): int
    {
        $size = trim($size);
        if (empty($size) || $size === '-1') {
            return 0; // Unlimited
        }

        $last = strtolower($size[strlen($size)-1]);
        $size = (int) $size;

        switch($last) {
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
        }

        return $size;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes): string
    {
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
