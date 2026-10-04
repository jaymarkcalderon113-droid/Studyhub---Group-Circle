<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\InteractsWithStudyGroups;
use App\Http\Controllers\Controller;
use App\Models\GroupMember;
use App\Models\StudyLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** STUDY SESSION TRACKING: log study time, see this week's totals and chart. */
class ProgressController extends Controller
{
    use InteractsWithStudyGroups;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $today = today();

        // Last 14 days of logs: the most recent 7 = "this week", the 7 before = "last week".
        $recent = StudyLog::where('user_id', $user->id)
            ->where('studied_on', '>=', $today->copy()->subDays(13))
            ->get()
            ->groupBy(fn ($l) => $l->studied_on->format('Y-m-d'))
            ->map(fn ($rows) => $rows->sum('minutes'));

        $sumDays = fn (int $from, int $to) => collect(range($from, $to))
            ->sum(fn ($i) => $recent->get($today->copy()->subDays($i)->format('Y-m-d'), 0));

        $thisWeek = $sumDays(0, 6);
        $lastWeek = $sumDays(7, 13);

        $chart = collect(range(6, 0))->map(fn ($i) => [
            'day' => $today->copy()->subDays($i)->format('D'),
            'hours' => round($recent->get($today->copy()->subDays($i)->format('Y-m-d'), 0) / 60, 1),
        ]);

        $logs = StudyLog::with('group:id,name')
            ->where('user_id', $user->id)
            ->orderByDesc('studied_on')->orderByDesc('id')
            ->limit(10)->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'group' => $l->group?->name ?? 'Solo study',
                'topic' => $l->topic,
                'date' => $l->studied_on->format('M j, Y'),
                'minutes' => $l->minutes,
            ]);

        return Inertia::render('progress', [
            'totalHours' => round($thisWeek / 60, 1),
            // % change vs last week (null when there is nothing to compare with)
            'change' => $lastWeek > 0 ? (int) round(($thisWeek - $lastWeek) / $lastWeek * 100) : null,
            'chart' => $chart,
            'logs' => $logs,
            'groups' => $this->groupTabs($this->memberGroups($request)),
            'today' => $today->format('Y-m-d'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'studied_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'minutes' => ['required', 'integer', 'between:5,720'],
            'topic' => ['nullable', 'string', 'max:100'],
            'study_group_id' => ['nullable', Rule::exists('group_members', 'study_group_id')
                ->where('user_id', $user->id)->where('status', GroupMember::APPROVED)],
        ]);

        $user->studyLogs()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Study time logged.']);

        return back();
    }

    public function destroy(Request $request, StudyLog $log): RedirectResponse
    {
        abort_unless($log->user_id === $request->user()->id, 403);

        $log->delete();

        return back();
    }
}
