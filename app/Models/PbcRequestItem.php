<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbcRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pbc_request_id', 'category', 'particulars', 'date_requested',
        'is_required', 'status', 'remarks', 'order_index', 'uploaded_at',
        'reviewed_at', 'reviewed_by', 'approved_document_id'
    ];

    protected $casts = [
        'date_requested' => 'date',
        'is_required' => 'boolean',
        'uploaded_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    // NEW: Category constants for wireframe alignment
    const CATEGORY_CURRENT_FILE = 'CF';
    const CATEGORY_PERMANENT_FILE = 'PF';

    public static function getCategories()
    {
        return [
            self::CATEGORY_CURRENT_FILE => 'Current File',
            self::CATEGORY_PERMANENT_FILE => 'Permanent File',
        ];
    }

    // Relationships
    public function pbcRequest()
    {
        return $this->belongsTo(PbcRequest::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents()
    {
        return $this->hasMany(DocumentUpload::class);
    }

    public function approvedDocument()
    {
        return $this->belongsTo(DocumentUpload::class, 'approved_document_id');
    }

    // Helper methods
    public function hasDocuments()
    {
        return $this->documents()->count() > 0;
    }

    public function getStatusColor()
    {
        return match($this->getCurrentStatus()) {
            'pending' => 'secondary',
            'uploaded' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    // SINGLE getCurrentStatus method - FIXED VERSION
    public function getCurrentStatus()
    {
        // If no documents uploaded
        if ($this->documents()->count() === 0) {
            return 'pending';
        }

        // Check for approved documents
        $approvedCount = $this->documents()->where('status', 'approved')->count();
        if ($approvedCount > 0) {
            return 'approved';
        }

        // Check for uploaded (pending review) documents
        $uploadedCount = $this->documents()->where('status', 'uploaded')->count();
        if ($uploadedCount > 0) {
            return 'uploaded';
        }

        // All remaining documents must be rejected
        return 'rejected';
    }

    // Get detailed document status summary
    public function getDocumentStatusSummary()
    {
        $documents = $this->documents;

        return [
            'total' => $documents->count(),
            'approved' => $documents->where('status', 'approved')->count(),
            'rejected' => $documents->where('status', 'rejected')->count(),
            'uploaded' => $documents->where('status', 'uploaded')->count(),
            'latest_approved' => $documents->where('status', 'approved')->sortByDesc('approved_at')->first(),
            'latest_upload' => $documents->sortByDesc('created_at')->first(),
        ];
    }

    // Check if item allows new uploads
    public function allowsNewUploads()
    {
        return $this->getCurrentStatus() !== 'approved';
    }

    // Get status badge class for UI
    public function getStatusBadgeClass()
    {
        return match($this->getCurrentStatus()) {
            'pending' => 'bg-secondary',
            'uploaded' => 'bg-warning',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    // Get human-readable status text
    public function getStatusText()
    {
        return match($this->getCurrentStatus()) {
            'pending' => 'Pending',
            'uploaded' => 'Uploaded',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    // NEW: Helper methods for category display
    public function getCategoryDisplayAttribute()
    {
        return self::getCategories()[$this->category] ?? $this->category;
    }

    public function getCategoryColorClass()
    {
        return match($this->category) {
            self::CATEGORY_CURRENT_FILE => 'badge-primary',
            self::CATEGORY_PERMANENT_FILE => 'badge-secondary',
            default => 'badge-light'
        };
    }

    public function getCategoryIconClass()
    {
        return match($this->category) {
            self::CATEGORY_CURRENT_FILE => 'fas fa-file',
            self::CATEGORY_PERMANENT_FILE => 'fas fa-folder',
            default => 'fas fa-file-alt'
        };
    }

    // NEW: Scope for filtering by category
    public function scopeCurrentFile($query)
    {
        return $query->where('category', self::CATEGORY_CURRENT_FILE);
    }

    public function scopePermanentFile($query)
    {
        return $query->where('category', self::CATEGORY_PERMANENT_FILE);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // NEW: Wireframe-specific helper methods
    public function getRequestorAttribute()
    {
        // For wireframe compatibility - return the person who created the PBC request
        return $this->pbcRequest->creator->name ?? 'MNGR 1';
    }

    public function getAssignedToAttribute()
    {
        // For wireframe - return who this is assigned to (usually client staff)
        return $this->pbcRequest->client->contact_person ?? 'Client Staff 1';
    }

    public function getDateRequestedFormattedAttribute()
    {
        return $this->date_requested?->format('d/m/Y') ?? now()->format('d/m/Y');
    }

    public function getDueDateAttribute()
    {
        return $this->pbcRequest->due_date;
    }

    public function getDueDateFormattedAttribute()
    {
        return $this->due_date?->format('d/m/Y') ?? '';
    }

    // NEW: Get progress percentage for this item
    public function getProgressPercentage()
    {
        return match($this->getCurrentStatus()) {
            'pending' => 0,
            'uploaded' => 50,
            'rejected' => 25,
            'approved' => 100,
            default => 0,
        };
    }

    // NEW: Check if item is overdue
    public function isOverdue()
    {
        return $this->due_date &&
               $this->due_date->isPast() &&
               $this->getCurrentStatus() !== 'approved';
    }

    // NEW: Get days outstanding
    public function getDaysOutstanding()
    {
        if (!$this->date_requested) {
            return 0;
        }

        return $this->date_requested->diffInDays(now());
    }

    // NEW: Get display status for wireframe
    public function getDisplayStatus()
    {
        if ($this->isOverdue()) {
            return 'overdue';
        }

        return $this->getCurrentStatus();
    }

    // NEW: Get status color for wireframe
    public function getWireframeStatusColor()
    {
        return match($this->getDisplayStatus()) {
            'pending' => '#6c757d',      // Gray
            'uploaded' => '#ffc107',     // Yellow
            'approved' => '#28a745',     // Green
            'rejected' => '#dc3545',     // Red
            'overdue' => '#dc3545',      // Red
            default => '#6c757d',
        };
    }

    // NEW: Get actions available for this item
    public function getAvailableActions()
    {
        $actions = [];
        $status = $this->getCurrentStatus();

        if ($status === 'uploaded') {
            $actions[] = 'approve';
            $actions[] = 'reject';
        }

        if (in_array($status, ['pending', 'rejected'])) {
            $actions[] = 'upload';
        }

        if ($status === 'approved') {
            $actions[] = 'view';
            $actions[] = 'download';
        }

        return $actions;
    }

    // NEW: Get note/remarks for wireframe
    public function getNoteAttribute()
    {
        return $this->remarks ?? 'Insert text';
    }
}
