<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use App\Services\ReminderService;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    protected $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    public function index()
    {
        $reminders = $this->reminderService->getUserReminders(auth()->id(), 50);
        $unreadCount = $this->reminderService->getUnreadCount(auth()->id());

        return view('client.reminders.index', compact('reminders', 'unreadCount'));
    }

    public function markAsRead($id)
    {
        $this->reminderService->markAsRead($id, auth()->id());

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        Reminder::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return redirect()->back()->with('success', 'All reminders marked as read.');
    }

    public function getUnreadCount()
    {
        $count = $this->reminderService->getUnreadCount(auth()->id());
        return response()->json(['count' => $count]);
    }
}
