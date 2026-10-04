<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\InteractsWithStudyGroups;
use App\Http\Controllers\Controller;
use App\Models\StudyGroup;
use App\Models\StudySession;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/** STUDY SCHEDULE: a month calendar of planned sessions for all my groups. */
class ScheduleController extends Controller
{
    use InteractsWithStudyGroups;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $request->validate(['month' => ['sometimes', 'date_format:Y-m']]);

        // ?month=2026-10 (defaults to the current month)
        $start = Carbon::createFromFormat('Y-m-d', $request->query('month', now()->format('Y-m')).'-01')->startOfDay();
        $end = $start->copy()->endOfMonth();

        $groups = $this->memberGroups($request);
        $groupIds = $groups->pluck('id');

        $toRow = fn (StudySession $s) => [
            'id' => $s->id,
            'group_id' => $s->study_group_id,
            'group' => $s->group->name,
            'title' => $s->title,
            'location' => $s->location,
            'day' => $s->starts_at->day,
            'date' => $s->starts_at->format('M j'),
            'time' => $s->starts_at->format('g:i A').' – '.$s->ends_at->format('g:i A'),
            // the creator, or the owner of the group, may delete
            'can_delete' => $s->created_by === $user->id || $s->group->owner_id === $user->id,
        ];

        $sessions = StudySession::with('group:id,name,owner_id')
            ->whereIn('study_group_id', $groupIds)
            ->whereBetween('starts_at', [$start, $end])
            ->orderBy('starts_at')
            ->get()
            ->map($toRow);

        $upcoming = StudySession::with('group:id,name,owner_id')
            ->whereIn('study_group_id', $groupIds)
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get()
            ->map($toRow);

        return Inertia::render('schedule', [
            'groups' => $this->groupTabs($groups),
            'sessions' => $sessions,
            'upcoming' => $upcoming,
            'calendar' => [
                'month' => $start->format('Y-m'),
                'label' => $start->format('F Y'),
                'prev' => $start->copy()->subMonth()->format('Y-m'),
                'next' => $start->copy()->addMonth()->format('Y-m'),
                'firstWeekday' => $start->dayOfWeek, // 0 = Sunday
                'daysInMonth' => $start->daysInMonth,
                'today' => now()->format('Y-m-d'),
            ],
        ]);
    }

    public function store(Request $request, StudyGroup $group, Notifier $notifier): RedirectResponse
    {
        $this->authorizeMember($request, $group);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:100'],
        ]);

        $group->sessions()->create([
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'starts_at' => Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['start_time']}"),
            'ends_at' => Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['end_time']}"),
            'location' => $data['location'] ?? null,
        ]);

        $when = Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['start_time']}")->format('M j, g:i A');
        $notifier->toGroup($group, $request->user(), 'session', "New session in {$group->name}", "{$data['title']} · {$when}", '/schedule?month='.substr($data['date'], 0, 7));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Session scheduled.']);

        return back();
    }

    public function destroy(Request $request, StudyGroup $group, StudySession $studySession): RedirectResponse
    {
        $this->authorizeMember($request, $group);
        abort_unless($studySession->study_group_id === $group->id, 404);
        abort_unless($studySession->created_by === $request->user()->id || $group->owner_id === $request->user()->id, 403);

        $studySession->delete();

        return back();
    }
}
