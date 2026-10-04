<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** ADMIN: search users and suspend / reactivate students. */
class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")))
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role->value,
                'suspended' => $u->isSuspended(),
                'joined' => $u->created_at->format('M j, Y'),
                'can_manage' => $u->role === UserRole::Student, // admins can't be suspended
            ]);

        return Inertia::render('admin/users', ['users' => $users, 'q' => $q]);
    }

    /** Toggle: suspended -> active, active -> suspended. */
    public function toggleSuspend(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, 'You cannot suspend yourself.');
        abort_if($user->role === UserRole::Admin, 403, 'Admins cannot be suspended.');

        $user->forceFill(['suspended_at' => $user->isSuspended() ? null : now()])->save();

        // If they were just suspended, drop their saved logins so they are signed out everywhere.
        if ($user->isSuspended()) {
            $user->forceFill(['remember_token' => null])->save();
        }

        \Inertia\Inertia::flash('toast', [
            'type' => 'success',
            'message' => $user->isSuspended() ? "{$user->name} was suspended." : "{$user->name} was reactivated.",
        ]);

        return back();
    }
}
