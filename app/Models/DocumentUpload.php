<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DocumentUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'pbc_request_item_id', 'original_filename', 'stored_filename',
        'file_path', 'file_extension', 'file_size', 'mime_type',
        'status', 'admin_notes', 'uploaded_by', 'approved_at', 'approved_by'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function pbcRequestItem()
    {
        return $this->belongsTo(PbcRequestItem::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Helper methods
    public function getFileSizeFormatted()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDownloadUrl()
    {
        return route('documents.download', $this->id);
    }

    public function canBeAccessedBy(User $user)
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Client can only access their own documents
        return $this->pbcRequestItem->pbcRequest->client_id === $user->client->id;
    }
}
