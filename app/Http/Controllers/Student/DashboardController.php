<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\InteractsWithStudyGroups;
use App\Http\Controllers\Controller;
use App\Models\GroupRating;
use App\Models\StudySession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Student dashboard: real numbers from groups, schedule, tasks, ratings, notifications. */
class DashboardController extends Controller
{
    use InteractsWithStudyGroups;

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $groups = $this->memberGroups($request);
        $groupIds = $groups->pluck('id');

        $upcomingQuery = StudySession::with('group:id,name')
            ->whereIn('study_group_id', $groupIds)
            ->where('ends_at', '>=', now());

        $average = GroupRating::whereIn('study_group_id', $groupIds)->avg('stars');

        return Inertia::render('dashboard', [
            'stats' => [
                'groups' => $groups->count(),
                'sessions' => (clone $upcomingQuery)->count(),
                'tasksDue' => $user->tasks()->whereNull('completed_at')->count(),
                'rating' => $average ? round((float) $average, 1) : null,
            ],
            'upcoming' => $upcomingQuery->orderBy('starts_at')->limit(3)->get()->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'group' => $s->group->name,
                'when' => $s->starts_at->format('D, M j · g:i A').' – '.$s->ends_at->format('g:i A'),
            ]),
            'notifications' => $user->userNotifications()->latest('id')->limit(4)->get()->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'time' => $n->created_at->diffForHumans(),
                'read' => $n->read_at !== null,
            ]),
            'groups' => $this->groupTabs($groups),
        ]);
    }
}
