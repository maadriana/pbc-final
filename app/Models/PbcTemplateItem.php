<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbcTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pbc_template_id', 'category', 'particulars', 'is_required', 'order_index'
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(PbcTemplate::class, 'pbc_template_id');
    }
}
