<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\StudySession;
use App\Models\StudyGroup;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

/** ADMIN DASHBOARD: platform totals. Route: /admin/dashboard (role:admin). */
class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => [
                'students' => User::where('role', UserRole::Student)->count(),
                'suspended' => User::whereNotNull('suspended_at')->count(),
                'groups' => StudyGroup::count(),
                'flagged' => StudyGroup::whereNotNull('flagged_at')->count(),
                'sessionsThisWeek' => StudySession::whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            ],
            'newestUsers' => User::latest('id')->limit(5)->get()->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'role' => $u->role->value,
                'joined' => $u->created_at->format('M j, Y'),
            ]),
            'flaggedGroups' => StudyGroup::with('owner:id,name')->whereNotNull('flagged_at')->latest('flagged_at')->limit(5)->get()
                ->map(fn ($g) => ['id' => $g->id, 'name' => $g->name, 'owner' => $g->owner->name]),
        ]);
    }
}
