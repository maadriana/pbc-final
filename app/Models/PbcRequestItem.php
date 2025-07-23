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
        'reviewed_at', 'reviewed_by'
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

    // Helper methods
    public function hasDocuments()
    {
        return $this->documents()->count() > 0;
    }

    public function getStatusColor()
    {
        return match($this->status) {
            'pending' => 'secondary',
            'uploaded' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }
}
