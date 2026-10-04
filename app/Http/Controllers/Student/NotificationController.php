<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** NOTIFICATIONS: list, mark one as read, mark all as read. Only ever MY notifications. */
class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('notifications', [
            'items' => $request->user()->userNotifications()->latest('id')->limit(50)->get()
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'body' => $n->body,
                    'link' => $n->link,
                    'read' => $n->read_at !== null,
                    'time' => $n->created_at->diffForHumans(),
                ]),
        ]);
    }

    public function read(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->userNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }
}
