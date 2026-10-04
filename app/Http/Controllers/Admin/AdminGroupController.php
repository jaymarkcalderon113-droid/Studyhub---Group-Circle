<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupMember;
use App\Models\StudyGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/** ADMIN: review all groups, flag them, or delete them. */
class AdminGroupController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $groups = StudyGroup::with(['owner:id,name', 'subject:id,name'])
            ->withCount(['memberships as members_count' => fn ($m) => $m->where('status', GroupMember::APPROVED)])
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderByRaw('flagged_at is null') // flagged groups first
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (StudyGroup $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'subject' => $g->subject->name,
                'owner' => $g->owner->name,
                'members' => $g->members_count,
                'max' => $g->max_members,
                'flagged' => $g->flagged_at !== null,
                'created' => $g->created_at->format('M j, Y'),
            ]);

        return Inertia::render('admin/groups', ['groups' => $groups, 'q' => $q]);
    }

    public function toggleFlag(StudyGroup $group): RedirectResponse
    {
        // forceFill: "flagged_at" is deliberately not mass-assignable on the model,
        // so a plain update([...]) would silently ignore it.
        $group->forceFill(['flagged_at' => $group->flagged_at ? null : now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => $group->flagged_at ? 'Group flagged.' : 'Flag removed.']);

        return back();
    }

    public function destroy(StudyGroup $group): RedirectResponse
    {
        // Database rows (members, chat, notes, files, sessions, ratings) are removed by
        // the cascading foreign keys. The uploaded files on disk need removing by hand.
        Storage::disk('local')->deleteDirectory("group-files/{$group->id}");

        $name = $group->name;
        $group->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Group “{$name}” was deleted."]);

        return back();
    }
}