<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'pbc_request_id',
        'user_id',
        'reminder_type',
        'message',
        'sent_at',
        'read_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime'
    ];

    public function pbcRequest()
    {
        return $this->belongsTo(PbcRequest::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isUnread()
    {
        return is_null($this->read_at);
    }

    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }
}
