<?php

namespace App\Http\Controllers\Concerns;

use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** Shared helpers for pages that work "inside one of my groups" (chat, notes, files). */
trait InteractsWithStudyGroups
{
    /** All groups the signed-in student belongs to. */
    protected function memberGroups(Request $request): Collection
    {
        return $request->user()->studyGroups()->with('subject')->orderBy('study_groups.name')->get();
    }

    /** The group to show: ?group=ID if it is one of mine, otherwise my first group. */
    protected function resolveGroup(Request $request, Collection $groups): ?StudyGroup
    {
        return $groups->firstWhere('id', (int) $request->query('group')) ?? $groups->first();
    }

    /** Stop (403) anyone who is not an approved member of the group. */
    protected function authorizeMember(Request $request, StudyGroup $group): void
    {
        abort_unless($group->hasApprovedMember($request->user()), 403);
    }

    /** The small group list used by the group picker tabs. */
    protected function groupTabs(Collection $groups): Collection
    {
        return $groups->map(fn ($g) => ['id' => $g->id, 'name' => $g->name])->values();
    }
}
