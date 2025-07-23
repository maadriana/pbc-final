<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbcRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id', 'client_id', 'project_id', 'title', 'description',
        'header_info', 'status', 'due_date', 'sent_at', 'completed_at', 'created_by'
    ];

    protected $casts = [
        'header_info' => 'array',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function template()
    {
        return $this->belongsTo(PbcTemplate::class, 'template_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PbcRequestItem::class)->orderBy('order_index');
    }

    // Helper methods - ONLY ONE VERSION OF EACH
    public function getProgressPercentage()
    {
        $total = $this->items()->count();
        if ($total === 0) return 0;

        $completed = $this->items()->where('status', 'approved')->count();
        return round(($completed / $total) * 100);
    }

    public function isOverdue()
    {
        return $this->due_date &&
               $this->due_date->isPast() &&
               !in_array($this->status, ['completed']);
    }

    public function getProgressDetails()
    {
        $totalItems = $this->items()->count();
        $pendingItems = $this->items()->where('status', 'pending')->count();
        $uploadedItems = $this->items()->where('status', 'uploaded')->count();
        $approvedItems = $this->items()->where('status', 'approved')->count();
        $rejectedItems = $this->items()->where('status', 'rejected')->count();

        return [
            'total' => $totalItems,
            'pending' => $pendingItems,
            'uploaded' => $uploadedItems,
            'approved' => $approvedItems,
            'rejected' => $rejectedItems,
            'percentage' => $totalItems > 0 ? round(($approvedItems / $totalItems) * 100) : 0,
        ];
    }

    public function getDaysOutstanding()
    {
        if (!$this->sent_at) {
            return 0;
        }

        return $this->sent_at->diffInDays(now());
    }

    public function getStatusForDisplay()
    {
        if ($this->isOverdue()) {
            return 'overdue';
        }

        return $this->status;
    }

}
