<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileHelper
{
    /**
     * Generate unique filename with timestamp and random string
     */
    public static function generateUniqueFilename($originalName)
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = pathinfo($originalName, PATHINFO_FILENAME);

        // Sanitize filename - remove special characters
        $filename = preg_replace('/[^A-Za-z0-9\-_]/', '_', $filename);
        $filename = trim($filename, '_');

        // Limit filename length
        if (strlen($filename) > 50) {
            $filename = substr($filename, 0, 50);
        }

        return $filename . '_' . time() . '_' . Str::random(8) . '.' . $extension;
    }

    /**
     * Get Font Awesome icon class based on file extension
     */
    public static function getFileIcon($extension)
    {
        $extension = strtolower($extension);

        return match($extension) {
            'pdf' => 'fas fa-file-pdf text-danger',
            'doc', 'docx' => 'fas fa-file-word text-primary',
            'xls', 'xlsx' => 'fas fa-file-excel text-success',
            'ppt', 'pptx' => 'fas fa-file-powerpoint text-warning',
            'zip', 'rar', '7z' => 'fas fa-file-archive text-secondary',
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp' => 'fas fa-file-image text-info',
            'txt', 'rtf' => 'fas fa-file-alt text-secondary',
            'csv' => 'fas fa-file-csv text-success',
            'html', 'css', 'js', 'php', 'py', 'java', 'cpp', 'c', 'json', 'xml' => 'fas fa-file-code text-warning',
            'mp3', 'wav', 'flac', 'aac' => 'fas fa-file-audio text-primary',
            'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm' => 'fas fa-file-video text-danger',
            default => 'fas fa-file text-secondary',
        };
    }

    /**
     * Format file size in human readable format
     */
    public static function getFileSizeFormatted($bytes)
    {
        if ($bytes == 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Alternative method name for backward compatibility
     */
    public static function formatFileSize($bytes)
    {
        return self::getFileSizeFormatted($bytes);
    }

    /**
     * Check if file extension is allowed
     */
    public static function isAllowedFileType($extension)
    {
        $allowedTypes = [
            'pdf', 'doc', 'docx', 'xls', 'xlsx',
            'png', 'jpg', 'jpeg', 'zip', 'txt',
            'csv', 'ppt', 'pptx', 'rar', '7z',
            'gif', 'bmp', 'svg', 'webp', 'rtf'
        ];

        return in_array(strtolower($extension), $allowedTypes);
    }

    /**
     * Get file type category based on extension
     */
    public static function getFileType($extension)
    {
        $extension = strtolower($extension);

        $typeMap = [
            'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf'],
            'spreadsheet' => ['xls', 'xlsx', 'csv'],
            'presentation' => ['ppt', 'pptx'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'],
            'archive' => ['zip', 'rar', '7z'],
            'code' => ['html', 'css', 'js', 'php', 'py', 'java', 'cpp', 'c', 'json', 'xml'],
            'audio' => ['mp3', 'wav', 'flac', 'aac'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
        ];

        foreach ($typeMap as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }

        return 'unknown';
    }

    /**
     * Get MIME type from file extension
     */
    public static function getMimeType($extension)
    {
        $extension = strtolower($extension);

        return match($extension) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'rtf' => 'application/rtf',
            default => 'application/octet-stream',
        };
    }

    /**
     * Validate file size (in bytes)
     */
    public static function isValidFileSize($fileSize, $maxSizeInMB = 50)
    {
        $maxSizeInBytes = $maxSizeInMB * 1024 * 1024; // Convert MB to bytes
        return $fileSize <= $maxSizeInBytes;
    }

    /**
     * Get maximum file size allowed (in bytes)
     */
    public static function getMaxFileSize()
    {
        // Default 50MB, can be configured
        return config('app.max_file_size', 50 * 1024 * 1024);
    }

    /**
     * Generate directory path for file storage
     */
    public static function generateStoragePath($clientId, $projectId, $requestId)
    {
        return "pbc-documents/{$clientId}/{$projectId}/{$requestId}";
    }

    /**
     * Sanitize filename for safe storage
     */
    public static function sanitizeFilename($filename)
    {
        // Remove path information
        $filename = basename($filename);

        // Replace dangerous characters
        $filename = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $filename);

        // Remove multiple underscores
        $filename = preg_replace('/_+/', '_', $filename);

        // Trim underscores from start and end
        $filename = trim($filename, '_');

        return $filename;
    }

    /**
     * Validate file for upload
     */
    public static function validateFile($file)
    {
        $errors = [];

        // Check file extension
        $extension = $file->getClientOriginalExtension();
        if (!self::isAllowedFileType($extension)) {
            $errors[] = "File type '{$extension}' is not allowed.";
        }

        // Check file size
        if ($file->getSize() > self::getMaxFileSize()) {
            $maxSize = self::getFileSizeFormatted(self::getMaxFileSize());
            $errors[] = "File size exceeds maximum allowed size of {$maxSize}.";
        }

        // Check if file is actually uploaded
        if (!$file->isValid()) {
            $errors[] = "File upload failed. Please try again.";
        }

        return $errors;
    }

    /**
     * Safely delete a file from storage
     */
    public static function deleteFile($filePath, $disk = 'local')
    {
        try {
            if (Storage::disk($disk)->exists($filePath)) {
                $deleted = Storage::disk($disk)->delete($filePath);

                if ($deleted) {
                    Log::info("File deleted successfully: {$filePath}");
                    return ['success' => true, 'message' => 'File deleted successfully'];
                } else {
                    Log::warning("Failed to delete file: {$filePath}");
                    return ['success' => false, 'message' => 'Failed to delete file from storage'];
                }
            } else {
                Log::warning("File not found for deletion: {$filePath}");
                return ['success' => false, 'message' => 'File not found in storage'];
            }
        } catch (\Exception $e) {
            Log::error("Error deleting file {$filePath}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error occurred while deleting file: ' . $e->getMessage()];
        }
    }

    /**
     * Safely delete multiple files from storage
     */
    public static function deleteFiles(array $filePaths, $disk = 'local')
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'total' => count($filePaths)
        ];

        foreach ($filePaths as $filePath) {
            $result = self::deleteFile($filePath, $disk);

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to delete {$filePath}: " . $result['message'];
            }
        }

        return $results;
    }

    /**
     * Check if file exists in storage
     */
    public static function fileExists($filePath, $disk = 'local')
    {
        try {
            return Storage::disk($disk)->exists($filePath);
        } catch (\Exception $e) {
            Log::error("Error checking file existence {$filePath}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get file size from storage
     */
    public static function getFileSize($filePath, $disk = 'local')
    {
        try {
            if (Storage::disk($disk)->exists($filePath)) {
                return Storage::disk($disk)->size($filePath);
            }
            return 0;
        } catch (\Exception $e) {
            Log::error("Error getting file size {$filePath}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Move file to a new location in storage
     */
    public static function moveFile($oldPath, $newPath, $disk = 'local')
    {
        try {
            if (Storage::disk($disk)->exists($oldPath)) {
                $moved = Storage::disk($disk)->move($oldPath, $newPath);

                if ($moved) {
                    Log::info("File moved successfully: {$oldPath} -> {$newPath}");
                    return ['success' => true, 'message' => 'File moved successfully'];
                } else {
                    Log::warning("Failed to move file: {$oldPath} -> {$newPath}");
                    return ['success' => false, 'message' => 'Failed to move file'];
                }
            } else {
                return ['success' => false, 'message' => 'Source file not found'];
            }
        } catch (\Exception $e) {
            Log::error("Error moving file {$oldPath} to {$newPath}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error occurred while moving file: ' . $e->getMessage()];
        }
    }

    /**
     * Clean up orphaned files (files that exist in storage but not in database)
     */
    public static function cleanupOrphanedFiles($directory = 'pbc-documents', $disk = 'local')
    {
        try {
            // Get all files in the directory
            $storageFiles = Storage::disk($disk)->allFiles($directory);

            // Get all file paths from database
            $dbFiles = \DB::table('document_uploads')
                ->whereNotNull('file_path')
                ->pluck('file_path')
                ->toArray();

            // Find orphaned files
            $orphanedFiles = array_diff($storageFiles, $dbFiles);

            if (empty($orphanedFiles)) {
                return ['success' => true, 'message' => 'No orphaned files found', 'deleted' => 0];
            }

            // Delete orphaned files
            $deleteResults = self::deleteFiles($orphanedFiles, $disk);

            Log::info("Cleanup completed: {$deleteResults['success']} files deleted, {$deleteResults['failed']} failed");

            return [
                'success' => true,
                'message' => "Cleanup completed: {$deleteResults['success']} orphaned files deleted",
                'deleted' => $deleteResults['success'],
                'failed' => $deleteResults['failed'],
                'errors' => $deleteResults['errors']
            ];

        } catch (\Exception $e) {
            Log::error("Error during orphaned files cleanup: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error occurred during cleanup: ' . $e->getMessage()];
        }
    }
}
