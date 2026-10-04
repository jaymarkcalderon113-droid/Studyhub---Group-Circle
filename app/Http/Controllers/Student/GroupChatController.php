<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\InteractsWithStudyGroups;
use App\Http\Controllers\Controller;
use App\Models\StudyGroup;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** GROUP CHAT. Members only. The page re-fetches messages every few seconds (polling). */
class GroupChatController extends Controller
{
    use InteractsWithStudyGroups;

    public function index(Request $request): Response
    {
        $groups = $this->memberGroups($request);
        $group = $this->resolveGroup($request, $groups);

        return Inertia::render('messages', [
            'groups' => $this->groupTabs($groups),
            'active' => $group ? [
                'id' => $group->id,
                'name' => $group->name,
                'subject' => $group->subject->name,
                'skill_level' => $group->skill_level,
                'max' => $group->max_members,
            ] : null,
            // Latest 100 messages, oldest first (so the newest is at the bottom).
            'messages' => $group
                ? $group->messages()->with('user:id,name')->latest('id')->limit(100)->get()->reverse()->values()
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'body' => $m->body,
                        'author' => $m->user->name,
                        'mine' => $m->user_id === $request->user()->id,
                        'time' => $m->created_at->format('M j, g:i A'),
                    ])
                : [],
            'members' => $group ? $group->members()->orderBy('users.name')->pluck('users.name') : [],
        ]);
    }

    public function store(Request $request, StudyGroup $group, Notifier $notifier): RedirectResponse
    {
        $this->authorizeMember($request, $group);

        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);

        $group->messages()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);
        $notifier->newMessage($group, $request->user(), $data['body']);

        return back();
    }
}
