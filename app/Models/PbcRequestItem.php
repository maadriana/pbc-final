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
}
