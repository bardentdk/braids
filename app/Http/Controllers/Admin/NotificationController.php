<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = AppNotification::where('user_id', $request->user()->id)
            ->when($request->unread, fn($q) => $q->unread())
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications->through(fn($n) => [
                'id'       => $n->id,
                'type'     => $n->type,
                'title'    => $n->title,
                'message'  => $n->message,
                'icon'     => $n->icon,
                'link'     => $n->link,
                'severity' => $n->severity,
                'is_read'  => $n->is_read,
                'read_at'  => $n->read_at?->locale('fr')->isoFormat('D MMM à HH:mm'),
                'date'     => $n->created_at->locale('fr')->isoFormat('D MMM à HH:mm'),
                'diff'     => $n->created_at->diffForHumans(),
            ]),
            'unread_count' => AppNotification::where('user_id', $request->user()->id)->unread()->count(),
        ]);
    }

    public function markRead(Request $request, int $id): RedirectResponse
    {
        AppNotification::where('id', $id)
                        ->where('user_id', $request->user()->id)
                        ->first()
                        ?->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        AppNotification::where('user_id', $request->user()->id)
                        ->unread()
                        ->update(['read_at' => now()]);

        return back()->with('success', 'Toutes les notifications ont été lues.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        AppNotification::where('id', $id)
                        ->where('user_id', $request->user()->id)
                        ->delete();

        return back()->with('success', 'Notification supprimée.');
    }
}