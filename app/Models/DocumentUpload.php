<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'pbc_request_item_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_size',
        'mime_type',
        'file_extension',
        'status',
        'uploaded_by',
        'approved_by',
        'approved_at',
        'admin_notes',
        'client_notes',
        'file_deleted_at',
        'file_deleted_by',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'file_deleted_at' => 'datetime',
        'file_size' => 'integer',
    ];

    // Relationships
    public function pbcRequestItem(): BelongsTo
    {
        return $this->belongsTo(PbcRequestItem::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function fileDeleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'file_deleted_by');
    }

    // Helper methods
    public function getFileSizeFormattedAttribute()
    {
        return static::formatFileSize($this->file_size);
    }

    public function getFileSizeFormatted()
    {
        return static::formatFileSize($this->file_size);
    }

    public function isFileAvailable(): bool
    {
        return !$this->file_deleted_at && $this->file_path && Storage::exists($this->file_path);
    }

    public function isFileDeleted(): bool
    {
        return !is_null($this->file_deleted_at);
    }

    public function getFileStatusAttribute(): string
    {
        if ($this->isFileDeleted()) {
            return 'File Deleted';
        }

        if (!$this->isFileAvailable()) {
            return 'File Missing';
        }

        return match($this->status) {
            'uploaded' => 'Available',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown'
        };
    }

    public function getDeletionInfoAttribute(): ?string
    {
        if (!$this->isFileDeleted()) {
            return null;
        }

        $deleterName = $this->fileDeleter ? $this->fileDeleter->name : 'Unknown User';
        $deletedAt = $this->file_deleted_at->format('M d, Y h:i A');

        return "Deleted by {$deleterName} on {$deletedAt}";
    }

    public function canBeDeleted(): bool
    {
        return in_array($this->status, ['approved', 'rejected']) && !$this->isFileDeleted();
    }

    public function getOriginalStatusAttribute(): string
    {
        if ($this->isFileDeleted()) {
            return match($this->status) {
                'approved' => 'Was Approved',
                'rejected' => 'Was Rejected',
                'uploaded' => 'Was Pending',
                default => 'Unknown Status'
            };
        }

        return ucfirst($this->status);
    }

    public function isFileAccessible(): bool
    {
        try {
            if ($this->isFileDeleted()) {
                return false;
            }

            if (!$this->file_path) {
                return false;
            }

            if (!Storage::disk('local')->exists($this->file_path)) {
                return false;
            }

            $size = Storage::disk('local')->size($this->file_path);
            return $size !== false;

        } catch (\Exception $e) {
            Log::error('File accessibility check failed', [
                'document_id' => $this->id,
                'file_path' => $this->file_path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getFileInfoAttribute(): array
    {
        return [
            'name' => $this->original_filename,
            'size' => $this->getFileSizeFormatted(),
            'type' => $this->mime_type,
            'extension' => $this->file_extension,
            'uploaded_at' => $this->created_at->format('M d, Y H:i A'),
            'uploaded_by' => $this->uploader->name ?? 'Unknown',
            'status' => $this->getFileStatusAttribute(),
            'is_accessible' => $this->isFileAccessible(),
            'is_large_file' => $this->file_size > 50 * 1024 * 1024,
            'download_url' => route('documents.download', $this->id)
        ];
    }

    public function isLargeFile(): bool
    {
        return $this->file_size > 50 * 1024 * 1024;
    }

    public function getFileIconAttribute(): string
    {
        $iconMap = [
            'pdf' => 'fas fa-file-pdf text-danger',
            'doc' => 'fas fa-file-word text-primary',
            'docx' => 'fas fa-file-word text-primary',
            'xls' => 'fas fa-file-excel text-success',
            'xlsx' => 'fas fa-file-excel text-success',
            'ppt' => 'fas fa-file-powerpoint text-warning',
            'pptx' => 'fas fa-file-powerpoint text-warning',
            'jpg' => 'fas fa-file-image text-info',
            'jpeg' => 'fas fa-file-image text-info',
            'png' => 'fas fa-file-image text-info',
            'zip' => 'fas fa-file-archive text-secondary',
            'rar' => 'fas fa-file-archive text-secondary',
            'txt' => 'fas fa-file-alt text-muted',
            'csv' => 'fas fa-file-csv text-success'
        ];

        return $iconMap[$this->file_extension] ?? 'fas fa-file text-muted';
    }

    public function canBeAccessedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isSystemAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            try {
                $hasAccess = $user->assignedProjects()
                    ->where('projects.id', $this->pbcRequestItem->pbcRequest->project_id)
                    ->exists();

                return $hasAccess;
            } catch (\Exception $e) {
                Log::error('Error checking admin document access', [
                    'document_id' => $this->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        }

        if ($user->isClient()) {
            try {
                $documentClientId = $this->pbcRequestItem->pbcRequest->client_id ?? null;
                $userClientId = $user->client->id ?? null;

                Log::info('Document access check', [
                    'document_id' => $this->id,
                    'user_id' => $user->id,
                    'document_client_id' => $documentClientId,
                    'user_client_id' => $userClientId,
                    'has_client_relationship' => !is_null($user->client)
                ]);

                return $documentClientId && $userClientId && $documentClientId === $userClientId;
            } catch (\Exception $e) {
                Log::error('Error checking client document access', [
                    'document_id' => $this->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        }

        return false;
    }

    public function canBeDownloadedBy($user): bool
    {
        return $this->canBeAccessedBy($user);
    }

    public function canBeDownloaded($user = null): bool
    {
        $user = $user ?? auth()->user();

        if ($this->isFileDeleted() || !$this->isFileAccessible()) {
            return false;
        }

        return $this->canBeAccessedBy($user);
    }

    public function getSecurityInfoAttribute(): array
    {
        return [
            'is_secure' => $this->isSecureFile(),
            'risk_level' => $this->getRiskLevel(),
            'scan_status' => $this->getScanStatus(),
            'access_level' => $this->getAccessLevel()
        ];
    }

    public function isSecureFile(): bool
    {
        $secureExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'txt', 'csv'];

        if (!in_array($this->file_extension, $secureExtensions)) {
            return false;
        }

        $suspiciousPatterns = ['/\.php$/i', '/\.exe$/i', '/\.bat$/i', '/\.js$/i'];
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $this->original_filename)) {
                return false;
            }
        }

        return true;
    }

    public function getRiskLevel(): string
    {
        if (!$this->isSecureFile()) {
            return 'high';
        }

        if (in_array($this->file_extension, ['zip', 'rar'])) {
            return 'medium';
        }

        if ($this->file_size > 100 * 1024 * 1024) {
            return 'medium';
        }

        return 'low';
    }

    public function getScanStatus(): string
    {
        return 'not_scanned';
    }

    public function getAccessLevel(): string
    {
        if ($this->pbcRequestItem->pbcRequest->project->is_confidential ?? false) {
            return 'confidential';
        }

        return 'standard';
    }

    // Static methods
    public static function formatFileSize($bytes): string
    {
        if (!$bytes || $bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    public static function getFileExtension($filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    public static function validateUploadedFile($file): array
    {
        $errors = [];

        if (!$file || !$file->isValid()) {
            $errors[] = 'Invalid file upload';
            return $errors;
        }

        $maxSize = 300 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            $errors[] = 'File size exceeds 300MB limit';
        }

        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'application/zip',
            'application/x-rar-compressed',
            'application/vnd.rar',
            'text/plain',
            'text/csv'
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'File type not allowed: ' . $file->getMimeType();
        }

        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'zip', 'rar', 'txt', 'csv'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'File extension not allowed: ' . $extension;
        }

        $filename = $file->getClientOriginalName();
        $suspiciousPatterns = [
            '/\.php$/i', '/\.exe$/i', '/\.bat$/i', '/\.cmd$/i', '/\.com$/i',
            '/\.pif$/i', '/\.scr$/i', '/\.vbs$/i', '/\.js$/i'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $filename)) {
                $errors[] = 'Potentially unsafe file type detected';
                break;
            }
        }

        if (strpos($filename, "\0") !== false) {
            $errors[] = 'Invalid characters in filename';
        }

        return $errors;
    }

    public static function getUploadStats(): array
    {
        $totalUploads = static::count();
        $totalSize = static::sum('file_size');
        $largeFiles = static::where('file_size', '>', 50 * 1024 * 1024)->count();
        $recentUploads = static::where('created_at', '>=', now()->subDays(30))->count();
        $deletedFiles = static::whereNotNull('file_deleted_at')->count();

        $statusCounts = static::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $extensionCounts = static::selectRaw('file_extension, COUNT(*) as count')
            ->groupBy('file_extension')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'file_extension')
            ->toArray();

        $sizeCategoryCounts = [
            'small' => static::where('file_size', '<=', 1024 * 1024)->count(),
            'medium' => static::whereBetween('file_size', [1024 * 1024 + 1, 10 * 1024 * 1024])->count(),
            'large' => static::whereBetween('file_size', [10 * 1024 * 1024 + 1, 50 * 1024 * 1024])->count(),
            'very_large' => static::where('file_size', '>', 50 * 1024 * 1024)->count()
        ];

        return [
            'total_uploads' => $totalUploads,
            'total_size' => $totalSize,
            'total_size_formatted' => static::formatFileSize($totalSize),
            'large_files_count' => $largeFiles,
            'recent_uploads' => $recentUploads,
            'deleted_files' => $deletedFiles,
            'average_file_size' => $totalUploads > 0 ? $totalSize / $totalUploads : 0,
            'average_file_size_formatted' => $totalUploads > 0 ? static::formatFileSize($totalSize / $totalUploads) : '0 B',
            'status_breakdown' => $statusCounts,
            'extension_breakdown' => $extensionCounts,
            'size_category_breakdown' => $sizeCategoryCounts
        ];
    }

    public static function getUploadPerformanceMetrics(): array
    {
        $averageUploadTime = static::whereNotNull('created_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_time')
            ->value('avg_time') ?? 0;

        $largeFileCount = static::where('file_size', '>', 100 * 1024 * 1024)->count();
        $totalFileCount = static::count();

        return [
            'average_upload_time' => round($averageUploadTime, 2),
            'large_file_percentage' => $totalFileCount > 0 ? round(($largeFileCount / $totalFileCount) * 100, 2) : 0,
            'success_rate' => $totalFileCount > 0 ? round((static::whereNotNull('file_path')->count() / $totalFileCount) * 100, 2) : 0,
            'storage_efficiency' => static::getStorageEfficiency()
        ];
    }

    private static function getStorageEfficiency(): float
    {
        $totalStorageUsed = static::sum('file_size');
        $uniqueFilesSize = static::selectRaw('SUM(DISTINCT file_size) as unique_size')->value('unique_size') ?? 0;

        if ($totalStorageUsed == 0) {
            return 100.0;
        }

        return round(($uniqueFilesSize / $totalStorageUsed) * 100, 2);
    }

    // Scopes
    public function scopeWithActiveFiles($query)
    {
        return $query->whereNull('file_deleted_at');
    }

    public function scopeWithDeletedFiles($query)
    {
        return $query->whereNotNull('file_deleted_at');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'uploaded');
    }

    public function scopeDeletable($query)
    {
        return $query->whereIn('status', ['approved', 'rejected'])
                    ->whereNull('file_deleted_at');
    }

    public function scopeDeletedBetween($query, $startDate, $endDate)
    {
        return $query->whereNotNull('file_deleted_at')
                    ->whereBetween('file_deleted_at', [$startDate, $endDate]);
    }

    public function scopeLargeFiles($query)
    {
        return $query->where('file_size', '>', 50 * 1024 * 1024);
    }

    public function scopeRecentUploads($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }

    public function scopeByExtension($query, $extension)
    {
        return $query->where('file_extension', strtolower($extension));
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            if (!$document->file_extension && $document->original_filename) {
                $document->file_extension = self::getFileExtension($document->original_filename);
            }

            if (!$document->status) {
                $document->status = 'uploaded';
            }

            Log::info('Document upload attempt', [
                'filename' => $document->original_filename,
                'size' => $document->file_size,
                'user_id' => $document->uploaded_by
            ]);
        });

        static::created(function ($document) {
            Log::info('Document created successfully', [
                'document_id' => $document->id,
                'filename' => $document->original_filename,
                'size_formatted' => $document->getFileSizeFormatted()
            ]);
        });

        static::deleting(function ($document) {
            Log::info('Document deletion attempt', [
                'document_id' => $document->id,
                'filename' => $document->original_filename,
                'deleted_by' => auth()->id()
            ]);
        });
    }
}
