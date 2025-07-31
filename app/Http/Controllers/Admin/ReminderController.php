<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PbcRequest;
use App\Models\Reminder;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function send(Request $request)
    {
        try {
            $request->validate([
                'pbc_request_id' => 'required|exists:pbc_requests,id',
                'reminder_type' => 'required|in:gentle,standard,urgent',
                'custom_message' => 'nullable|string|max:500'
            ]);

            $pbcRequest = PbcRequest::with(['client.user', 'project'])->findOrFail($request->pbc_request_id);

            // Check permission
            if (!auth()->user()->canCreatePbcRequests()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }

            // Create reminder record
            Reminder::create([
                'pbc_request_id' => $pbcRequest->id,
                'user_id' => auth()->id(),
                'reminder_type' => $request->reminder_type,
                'message' => $request->custom_message ?: $this->getDefaultMessage($request->reminder_type),
                'sent_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reminder sent to client dashboard!'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send reminder.'], 500);
        }
    }

    private function getDefaultMessage($type)
    {
        return match($type) {
            'gentle' => 'Friendly reminder to upload your documents when convenient.',
            'standard' => 'Please upload the required documents for this request.',
            'urgent' => 'URGENT: Please upload the required documents immediately.',
            default => 'Please upload the required documents.'
        };
    }
}
