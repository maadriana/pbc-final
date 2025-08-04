<?php

namespace App\Http\Controllers;

use App\Models\DocumentUpload;
use App\Models\PbcRequestItem;
use App\Http\Requests\FileUploadRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function upload(FileUploadRequest $request)
    {
        $pbcRequestItem = PbcRequestItem::findOrFail($request->pbc_request_item_id);

        // Check if user has permission to upload to this item
        if (auth()->user()->isClient()) {
            $clientId = auth()->user()->client->id;
            if ($pbcRequestItem->pbcRequest->client_id !== $clientId) {
                abort(403, 'Unauthorized to upload to this request.');
            }
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        // Generate unique filename
        $storedName = Str::uuid() . '.' . $extension;

        // Create directory structure: client_id/project_id/request_id/
        $directory = 'pbc-documents/' .
                    $pbcRequestItem->pbcRequest->client_id . '/' .
                    $pbcRequestItem->pbcRequest->project_id . '/' .
                    $pbcRequestItem->pbcRequest->id;

        // Store file
        $filePath = $file->storeAs($directory, $storedName, 'local');

        // Create document record
        $document = DocumentUpload::create([
            'pbc_request_item_id' => $pbcRequestItem->id,
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $filePath,
            'file_extension' => $extension,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
        ]);

        // Update PBC request item status
        $pbcRequestItem->update([
            'status' => 'uploaded',
            'uploaded_at' => now(),
        ]);

        // Update PBC request status to in_progress if it's still pending
        if ($pbcRequestItem->pbcRequest->status === 'pending') {
            $pbcRequestItem->pbcRequest->update(['status' => 'in_progress']);
        }

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully.',
            'document' => [
                'id' => $document->id,
                'filename' => $document->original_filename,
                'size' => $document->getFileSizeFormatted(),
                'uploaded_at' => $document->created_at->format('M d, Y H:i'),
            ]
        ]);
    }

    /**
     * Enhanced upload file for specific PBC request item (for admin/staff upload)
     * Supports 300MB files with better error handling and progress tracking
     */
    public function uploadForItem(Request $request, PbcRequestItem $item)
{
    // Set error handling for large uploads
    ini_set('memory_limit', '1024M');
    set_time_limit(900); // 15 minutes

    // Validate configuration first
    $configErrors = $this->validatePhpConfiguration();
    if (!empty($configErrors)) {
        Log::warning('PHP configuration issues detected', $configErrors);

        return response()->json([
            'success' => false,
            'message' => 'Server configuration issue: ' . implode(', ', $configErrors),
            'error_type' => 'configuration'
        ], 500);
    }

    // Check if POST data was received properly
    if (!$request->hasFile('document')) {
        return response()->json([
            'success' => false,
            'message' => 'No file received. This may be due to file size exceeding server limits.',
            'error_type' => 'no_file',
            'debug_info' => [
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'content_length' => $request->header('Content-Length'),
                'request_size' => strlen(serialize($_POST)) + strlen(serialize($_FILES))
            ]
        ], 400);
    }

    try {
        // Enhanced validation with better error messages
        $request->validate([
            'document' => [
                'required',
                'file',
                'max:307200', // 300MB in KB
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,ppt,pptx,zip,rar,txt,csv'
            ],
            'notes' => 'nullable|string|max:500'
        ], [
            'document.required' => 'Please select a file to upload.',
            'document.file' => 'The uploaded file is not valid.',
            'document.max' => 'The file size cannot exceed 300MB.',
            'document.mimes' => 'Only PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, PNG, JPG, JPEG, ZIP, RAR, TXT, and CSV files are allowed.',
            'notes.max' => 'Notes cannot exceed 500 characters.'
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed: ' . collect($e->errors())->flatten()->first(),
            'error_type' => 'validation',
            'errors' => $e->errors()
        ], 422);
    }

    // Check permissions
    if (auth()->user()->isClient()) {
        $clientId = auth()->user()->client->id;
        if ($item->pbcRequest->client_id !== $clientId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to upload to this request.',
                'error_type' => 'permission'
            ], 403);
        }
    } else {
        if (!auth()->user()->isSystemAdmin()) {
            $hasAccess = auth()->user()->assignedProjects()
                ->where('projects.id', $item->pbcRequest->project_id)
                ->exists();

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this project.',
                    'error_type' => 'permission'
                ], 403);
            }
        }
    }

    try {
        $file = $request->file('document');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        // Validate file integrity
        if (!$file->isValid()) {
            $error = $file->getError();
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension'
            ];

            throw new \Exception($errorMessages[$error] ?? 'File upload error: ' . $error);
        }

        // Additional file size check
        if ($file->getSize() > 314572800) { // 300MB in bytes
            throw new \Exception('File size exceeds 300MB limit. Actual size: ' . DocumentUpload::formatFileSize($file->getSize()));
        }

        // Server-side file validation
        $validationErrors = DocumentUpload::validateUploadedFile($file);
        if (!empty($validationErrors)) {
            throw new \Exception('File validation failed: ' . implode(', ', $validationErrors));
        }

        // Generate unique filename
        $storedName = Str::uuid() . '.' . $extension;

        // Create directory structure
        $directory = 'pbc-documents/' .
                    $item->pbcRequest->client_id . '/' .
                    $item->pbcRequest->project_id . '/' .
                    $item->pbcRequest->id;

        // Ensure directory exists
        $fullPath = storage_path('app/' . $directory);
        if (!file_exists($fullPath)) {
            if (!mkdir($fullPath, 0755, true)) {
                throw new \Exception('Failed to create storage directory');
            }
        }

        // Store file with error handling
        $filePath = $file->storeAs($directory, $storedName, 'local');

        if (!$filePath) {
            throw new \Exception('Failed to store file on server');
        }

        // Verify file was actually stored
        if (!Storage::disk('local')->exists($filePath)) {
            throw new \Exception('File storage verification failed');
        }

        // Create document record
        $document = DocumentUpload::create([
            'pbc_request_item_id' => $item->id,
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $filePath,
            'file_extension' => $extension,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
            'admin_notes' => $request->notes,
            'status' => 'uploaded'
        ]);

        // Update statuses
        $item->update([
            'status' => 'uploaded',
            'uploaded_at' => now(),
        ]);

        if ($item->pbcRequest->status === 'pending') {
            $item->pbcRequest->update(['status' => 'in_progress']);
        }

        // Log successful upload
        Log::info('Large file upload successful', [
            'document_id' => $document->id,
            'file_size' => $file->getSize(),
            'filename' => $originalName,
            'user_id' => auth()->id(),
            'upload_time' => now(),
            'file_size_formatted' => DocumentUpload::formatFileSize($file->getSize())
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully.',
            'document' => [
                'id' => $document->id,
                'filename' => $document->original_filename,
                'size' => $document->getFileSizeFormatted(),
                'uploaded_at' => $document->created_at->format('M d, Y H:i'),
                'uploaded_by' => auth()->user()->name
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES);

    } catch (\Exception $e) {
        Log::error('Large file upload error', [
            'error' => $e->getMessage(),
            'item_id' => $item->id,
            'user_id' => auth()->id(),
            'file_size' => $request->file('document') ? $request->file('document')->getSize() : 'unknown',
            'trace' => $e->getTraceAsString(),
            'php_errors' => error_get_last()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to upload file: ' . $e->getMessage(),
            'error_type' => 'upload_failed',
            'debug_info' => [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'time_limit' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size')
            ]
        ], 500, [], JSON_UNESCAPED_SLASHES);
    }
}

    /**
     * Enhanced download method with better error handling and access control
     */
    public function download(DocumentUpload $document)
    {
        try {
            Log::info('Download attempt', [
                'document_id' => $document->id,
                'user_id' => auth()->id(),
                'user_role' => auth()->user()->role,
                'filename' => $document->original_filename
            ]);

            // Check if file has been deleted
            if ($document->isFileDeleted()) {
                Log::warning('Attempt to download deleted file', [
                    'document_id' => $document->id,
                    'user_id' => auth()->id()
                ]);
                abort(404, 'This file has been deleted and is no longer available.');
            }

            // Check if user has permission to download this file
            if (!$document->canBeAccessedBy(auth()->user())) {
                Log::warning('Unauthorized download attempt', [
                    'document_id' => $document->id,
                    'user_id' => auth()->id(),
                    'user_role' => auth()->user()->role
                ]);
                abort(403, 'You do not have permission to access this file.');
            }

            // Check if file exists in storage
            if (!Storage::disk('local')->exists($document->file_path)) {
                Log::error('File not found in storage', [
                    'document_id' => $document->id,
                    'file_path' => $document->file_path,
                    'user_id' => auth()->id()
                ]);
                abort(404, 'File not found on server.');
            }

            // Check file accessibility
            if (!$document->isFileAccessible()) {
                Log::error('File not accessible', [
                    'document_id' => $document->id,
                    'file_path' => $document->file_path,
                    'user_id' => auth()->id()
                ]);
                abort(404, 'File is not accessible.');
            }

            Log::info('File download successful', [
                'document_id' => $document->id,
                'user_id' => auth()->id(),
                'filename' => $document->original_filename
            ]);

            // Return the file download with proper headers for large files
            return Storage::disk('local')->download(
                $document->file_path,
                $document->original_filename,
                [
                    'Content-Type' => $document->mime_type ?? 'application/octet-stream',
                    'Content-Length' => $document->file_size,
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0'
                ]
            );

        } catch (\Exception $e) {
            Log::error('Download error', [
                'document_id' => $document->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw HTTP exceptions (like 403, 404)
            if (method_exists($e, 'getStatusCode')) {
                throw $e;
            }

            // For other exceptions, return 500
            abort(500, 'An error occurred while downloading the file.');
        }
    }

    /**
     * Enhanced delete method with better access control
     */
    public function delete(DocumentUpload $document)
    {
        // Only allow clients to delete their own documents, and only if not reviewed yet
        if (auth()->user()->isClient()) {
            if ($document->uploaded_by !== auth()->id() || $document->status !== 'uploaded') {
                abort(403, 'Cannot delete this document.');
            }
        } else {
            // Admin/staff can delete documents if they have project access
            if (!auth()->user()->isSystemAdmin()) {
                $hasAccess = auth()->user()->assignedProjects()
                    ->where('projects.id', $document->pbcRequestItem->pbcRequest->project_id)
                    ->exists();

                if (!$hasAccess) {
                    abort(403, 'You do not have access to delete this document.');
                }
            }
        }

        try {
            // Delete file from storage
            if (Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }

            // Update PBC request item status back to pending if no other documents
            $pbcRequestItem = $document->pbcRequestItem;
            $document->delete();

            if (!$pbcRequestItem->documents()->exists()) {
                $pbcRequestItem->update([
                    'status' => 'pending',
                    'uploaded_at' => null,
                ]);
            }

            Log::info('Document deleted successfully', [
                'document_id' => $document->id,
                'filename' => $document->original_filename,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Document deletion error', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system upload limits for frontend validation
     */
    public function getUploadLimits()
    {
        return response()->json([
            'max_file_size' => min(
                $this->parseSize(ini_get('upload_max_filesize')),
                $this->parseSize(ini_get('post_max_size')),
                314572800 // 300MB application limit
            ),
            'max_file_size_formatted' => '300MB',
            'allowed_extensions' => [
                'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
                'jpg', 'jpeg', 'png', 'zip', 'rar', 'txt', 'csv'
            ],
            'php_limits' => [
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_execution_time' => ini_get('max_execution_time'),
                'max_input_time' => ini_get('max_input_time'),
                'memory_limit' => ini_get('memory_limit')
            ],
            'server_info' => [
                'php_version' => PHP_VERSION,
                'max_upload_size_bytes' => min(
                    $this->parseSize(ini_get('upload_max_filesize')),
                    $this->parseSize(ini_get('post_max_size'))
                ),
                'recommended_settings' => [
                    'upload_max_filesize' => '300M',
                    'post_max_size' => '300M',
                    'max_execution_time' => '600',
                    'max_input_time' => '600',
                    'memory_limit' => '512M'
                ]
            ]
        ]);
    }

    /**
     * Validate file before processing (AJAX endpoint)
     */
    public function validateFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file'
        ]);

        $file = $request->file('file');
        $validationErrors = DocumentUpload::validateUploadedFile($file);

        $response = [
            'valid' => empty($validationErrors),
            'errors' => $validationErrors,
            'file_info' => [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'size_formatted' => DocumentUpload::formatFileSize($file->getSize()),
                'type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension()
            ]
        ];

        if (empty($validationErrors)) {
            $response['message'] = 'File is valid and ready for upload.';
        } else {
            $response['message'] = 'File validation failed: ' . implode(', ', $validationErrors);
        }

        return response()->json($response);
    }

    /**
     * Validate PHP configuration for large file uploads
     */
    private function validatePhpConfiguration(): array
{
    $errors = [];

    $uploadMaxBytes = $this->parseSize(ini_get('upload_max_filesize'));
    if ($uploadMaxBytes < 314572800) {
        $errors[] = "upload_max_filesize too small: " . ini_get('upload_max_filesize');
    }

    $postMaxBytes = $this->parseSize(ini_get('post_max_size'));
    if ($postMaxBytes < 367001600) { // 350MB
        $errors[] = "post_max_size too small: " . ini_get('post_max_size');
    }

    $maxExecutionTime = ini_get('max_execution_time');
    if ($maxExecutionTime > 0 && $maxExecutionTime < 600) {
        $errors[] = "max_execution_time too short: {$maxExecutionTime}s";
    }

    $memoryLimit = $this->parseSize(ini_get('memory_limit'));
    if ($memoryLimit > 0 && $memoryLimit < 536870912) {
        $errors[] = "memory_limit too low: " . ini_get('memory_limit');
    }

    return $errors;
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
     * Get upload statistics for admin dashboard
     */
    public function getUploadStats()
    {
        try {
            $stats = DocumentUpload::getUploadStats();

            // Add additional server stats
            $serverStats = [
                'disk_free_space' => disk_free_space(storage_path('app')),
                'disk_total_space' => disk_total_space(storage_path('app')),
                'upload_limit_bytes' => min(
                    $this->parseSize(ini_get('upload_max_filesize')),
                    $this->parseSize(ini_get('post_max_size'))
                ),
                'memory_limit_bytes' => $this->parseSize(ini_get('memory_limit')),
                'max_execution_time' => ini_get('max_execution_time')
            ];

            $serverStats['disk_usage_percentage'] = (($serverStats['disk_total_space'] - $serverStats['disk_free_space']) / $serverStats['disk_total_space']) * 100;

            return response()->json([
                'success' => true,
                'upload_stats' => $stats,
                'server_stats' => $serverStats,
                'configuration_issues' => $this->validatePhpConfiguration()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get upload stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upload statistics'
            ], 500);
        }
    }
}
