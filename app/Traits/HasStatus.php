<?php

namespace App\Traits;

trait HasStatus
{
    public function getStatusBadgeClass()
    {
        return match($this->status) {
            'pending' => 'bg-secondary',
            'in_progress' => 'bg-warning',
            'uploaded' => 'bg-info',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'completed' => 'bg-success',
            'overdue' => 'bg-danger',
            'active' => 'bg-success',
            'on_hold' => 'bg-warning',
            default => 'bg-secondary',
        };
    }

    public function getStatusText()
    {
        return match($this->status) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'uploaded' => 'Uploaded',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
            'overdue' => 'Overdue',
            'active' => 'Active',
            'on_hold' => 'On Hold',
            default => ucfirst($this->status),
        };
    }
}
