<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display a listing of all notifications for the authenticated user.
     */
    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Notification $notification): RedirectResponse
    {
        // Ensure the notification belongs to the authenticated user
        if ($notification->user_id !== auth()->id()) {
            abort(403, 'Вы не можете отметить это уведомление');
        }

        // Mark as read if not already read
        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return redirect()->back()->with('success', 'Уведомление отмечено как прочитанное');
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(): RedirectResponse
    {
        auth()->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()->back()->with('success', 'Все уведомления отмечены как прочитанные');
    }
}
