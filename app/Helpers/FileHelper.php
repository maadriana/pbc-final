<?php

namespace App\Helpers;

use Illuminate\Support\Str;

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
     * Check if file extension is allowed
     */
    public static function isAllowedFileType($extension)
    {
        $allowedTypes = [
            'pdf', 'doc', 'docx', 'xls', 'xlsx',
            'png', 'jpg', 'jpeg', 'zip', 'txt',
            'csv', 'ppt', 'pptx'
        ];

        return in_array(strtolower($extension), $allowedTypes);
    }

    /**
     * Get file type icon class for display
     */
    public static function getFileIcon($extension)
    {
        $extension = strtolower($extension);

        return match($extension) {
            'pdf' => 'fas fa-file-pdf text-danger',
            'doc', 'docx' => 'fas fa-file-word text-primary',
            'xls', 'xlsx' => 'fas fa-file-excel text-success',
            'ppt', 'pptx' => 'fas fa-file-powerpoint text-warning',
            'zip', 'rar' => 'fas fa-file-archive text-secondary',
            'jpg', 'jpeg', 'png', 'gif' => 'fas fa-file-image text-info',
            'txt' => 'fas fa-file-alt text-secondary',
            'csv' => 'fas fa-file-csv text-success',
            default => 'fas fa-file text-secondary',
        };
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
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
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
}
