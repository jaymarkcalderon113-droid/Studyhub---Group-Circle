<?php

namespace App\Services;

use App\Models\GroupMember;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * AUTOMATIC GROUP MATCHING.
 *
 * Every group the student could join gets a score out of 100:
 *   subject matches one of the student's subjects ... 40
 *   skill level matches ........................... 20
 *   study goal matches ............................ 20
 *   study preference (online/in-person) matches ... 20
 * Groups with a score of 0, full groups, and groups the student already
 * belongs to are left out. Best matches come first.
 */
class GroupMatcher
{
    /**
     * @param  array{subjects: array<int>, skill_level: string, study_goal: string, study_mode: string}  $criteria
     * @return Collection<int, array<string, mixed>>
     */
    public function match(User $user, array $criteria): Collection
    {
        $groups = StudyGroup::with('subject')
            ->withCount(['memberships as members_count' => fn ($q) => $q->where('status', GroupMember::APPROVED)])
            ->withAvg('ratings as rating', 'stars')
            ->withCount('ratings as reviews')
            ->whereDoesntHave('memberships', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('status', GroupMember::APPROVED))
            ->get();

        // Groups this student already asked to join (button shows "Requested").
        $requested = GroupMember::where('user_id', $user->id)
            ->where('status', GroupMember::PENDING)
            ->pluck('study_group_id')
            ->all();

        return $groups
            ->reject(fn ($g) => $g->members_count >= $g->max_members)
            ->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'subject' => $g->subject->name,
                'skill_level' => $g->skill_level,
                'study_goal' => $g->study_goal,
                'study_mode' => $g->study_mode,
                'members' => $g->members_count,
                'max' => $g->max_members,
                'rating' => $g->rating ? round((float) $g->rating, 1) : null,
                'reviews' => $g->reviews,
                'score' => $this->score($g, $criteria),
                'requested' => in_array($g->id, $requested, true),
            ])
            ->filter(fn ($row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->values();
    }

    /** @param  array<string, mixed>  $criteria */
    private function score(StudyGroup $group, array $criteria): int
    {
        return (in_array($group->subject_id, $criteria['subjects'], true) ? 40 : 0)
            + ($group->skill_level === $criteria['skill_level'] ? 20 : 0)
            + ($group->study_goal === $criteria['study_goal'] ? 20 : 0)
            + ($group->study_mode === $criteria['study_mode'] ? 20 : 0);
    }
}
