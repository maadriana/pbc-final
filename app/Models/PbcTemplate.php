<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbcTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'header_info', 'is_active', 'created_by'
    ];

    protected $casts = [
        'header_info' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pbcRequests()
    {
        return $this->hasMany(PbcRequest::class, 'template_id');
    }

    public function templateItems()
    {
        return $this->hasMany(PbcTemplateItem::class, 'pbc_template_id')->orderBy('order_index');
    }

    // Helper methods
    public function getItemsCount()
    {
        return $this->templateItems()->count();
    }

    public function getRequiredItemsCount()
    {
        return $this->templateItems()->where('is_required', true)->count();
    }

    public function getCategories()
    {
        return $this->templateItems()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values();
    }
}
