<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\PbcRequest;
use App\Models\PbcRequestItem;
use App\Models\DocumentUpload;
use Carbon\Carbon;

class ReminderService
{
    public function createOverdueReminders()
    {
        $overdueRequests = PbcRequest::where('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->with(['client.user', 'items'])
            ->get();

        $count = 0;
        foreach ($overdueRequests as $request) {
            if ($this->createReminder(
                $request->client->user_id,
                $request->id,
                null,
                'Overdue: ' . $request->title,
                "PBC request '{$request->title}' is overdue. Due date was {$request->due_date->format('M d, Y')}.",
                Reminder::TYPE_OVERDUE,
                $request->due_date
            )) {
                $count++;
            }
        }

        return $count;
    }

    public function createDueSoonReminders()
    {
        $dueSoonRequests = PbcRequest::whereBetween('due_date', [now(), now()->addDays(3)])
            ->where('status', '!=', 'completed')
            ->with(['client.user'])
            ->get();

        $count = 0;
        foreach ($dueSoonRequests as $request) {
            if ($this->createReminder(
                $request->client->user_id,
                $request->id,
                null,
                'Due Soon: ' . $request->title,
                "PBC request '{$request->title}' is due on {$request->due_date->format('M d, Y')}.",
                Reminder::TYPE_DUE_SOON,
                $request->due_date
            )) {
                $count++;
            }
        }

        return $count;
    }

    public function createPendingReviewReminders()
    {
        $pendingItems = PbcRequestItem::whereHas('documents', function($q) {
            $q->where('status', 'uploaded');
        })->with(['pbcRequest.client.user', 'documents'])->get();

        $count = 0;
        foreach ($pendingItems as $item) {
            $pendingCount = $item->documents()->where('status', 'uploaded')->count();

            if ($this->createReminder(
                $item->pbcRequest->client->user_id,
                $item->pbcRequest->id,
                $item->id,
                'Pending Review: ' . \Str::limit($item->particulars, 50),
                "You have {$pendingCount} document(s) pending admin review for '{$item->particulars}'.",
                Reminder::TYPE_PENDING_REVIEW
            )) {
                $count++;
            }
        }

        return $count;
    }

    public function createDocumentRejectedReminders()
    {
        $rejectedDocs = DocumentUpload::where('status', 'rejected')
            ->where('updated_at', '>=', now()->subHours(6)) // Only recent rejections
            ->with(['pbcRequestItem.pbcRequest.client.user'])
            ->get();

        $count = 0;
        foreach ($rejectedDocs as $document) {
            if ($this->createReminder(
                $document->pbcRequestItem->pbcRequest->client->user_id,
                $document->pbcRequestItem->pbcRequest->id,
                $document->pbcRequestItem->id,
                'Document Rejected: ' . $document->original_filename,
                "Your document '{$document->original_filename}' was rejected. Reason: {$document->admin_notes}",
                Reminder::TYPE_DOCUMENT_REJECTED
            )) {
                $count++;
            }
        }

        return $count;
    }

    private function createReminder($userId, $pbcRequestId, $pbcRequestItemId, $title, $message, $type, $dueDate = null)
    {
        // Check if reminder already exists to avoid duplicates
        $exists = Reminder::where('user_id', $userId)
            ->where('pbc_request_id', $pbcRequestId)
            ->where('pbc_request_item_id', $pbcRequestItemId)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if (!$exists) {
            Reminder::create([
                'user_id' => $userId,
                'pbc_request_id' => $pbcRequestId,
                'pbc_request_item_id' => $pbcRequestItemId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'due_date' => $dueDate,
                'sent_at' => now()
            ]);
            return true;
        }

        return false;
    }

    public function getUserReminders($userId, $limit = 10)
    {
        return Reminder::forUser($userId)
            ->with(['pbcRequest', 'pbcRequestItem'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function markAsRead($reminderId, $userId)
    {
        return Reminder::where('id', $reminderId)
            ->where('user_id', $userId)
            ->update(['is_read' => true]);
    }

    public function getUnreadCount($userId)
    {
        return Reminder::forUser($userId)->unread()->count();
    }

    public function deleteOldReminders($days = 30)
    {
        return Reminder::where('created_at', '<', now()->subDays($days))
            ->where('is_read', true)
            ->delete();
    }
}
