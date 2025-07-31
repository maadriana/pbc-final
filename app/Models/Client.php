<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'company_name', 'contact_person',
        'phone', 'address', 'created_by'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // FIXED: Changed from belongsToMany to hasMany (one-to-many relationship)
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function pbcRequests()
    {
        return $this->hasMany(PbcRequest::class);
    }

    // NEW: Helper methods for wireframe compatibility
    public function getOngoingProjectsCount()
    {
        return $this->projects()->whereIn('status', ['active', 'on_hold'])->count();
    }

    public function getCompletedProjectsCount()
    {
        return $this->projects()->where('status', 'completed')->count();
    }

    public function getActivePbcRequestsCount()
    {
        return $this->pbcRequests()->whereIn('status', ['pending', 'in_progress'])->count();
    }

    public function getPendingPbcRequestsCount()
    {
        return $this->pbcRequests()->where('status', 'pending')->count();
    }

    public function getSubmittedPbcRequestsCount()
    {
        return $this->pbcRequests()->where('status', 'in_progress')->count();
    }

    // NEW: Get SEC registration and Tax ID for wireframe
    public function getSecRegistrationAttribute()
    {
        // This would come from additional client fields or be derived
        return 'ABC-000-0000'; // Placeholder - add to migration if needed
    }

    public function getTaxIdAttribute()
    {
        // This would come from additional client fields or be derived
        return '000-000-001'; // Placeholder - add to migration if needed
    }

    // NEW: Get contact information formatted for wireframe
    public function getFormattedAddressAttribute()
    {
        return $this->address ?: 'Address not provided';
    }

    public function getContactEmailAttribute()
    {
        return $this->user->email ?? '';
    }

    public function getContactPhoneAttribute()
    {
        return $this->phone ?: 'Phone not provided';
    }
}
