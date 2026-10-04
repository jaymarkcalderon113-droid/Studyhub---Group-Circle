<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\InteractsWithStudyGroups;
use App\Http\Controllers\Controller;
use App\Models\GroupMember;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** TASKS / CHECKLIST. Every task belongs to ONE student; nobody else can touch it. */
class TaskController extends Controller
{
    use InteractsWithStudyGroups;

    public function index(Request $request): Response
    {
        $today = today();

        $tasks = Task::with('group:id,name')
            ->where('user_id', $request->user()->id)
            ->orderByRaw('completed_at is not null') // pending first
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'due' => $t->due_date?->format('M j, Y'),
                'done' => $t->isDone(),
                'overdue' => ! $t->isDone() && $t->due_date !== null && $t->due_date->lt($today),
                'group' => $t->group?->name,
            ]);

        return Inertia::render('tasks', [
            'tasks' => $tasks,
            'groups' => $this->groupTabs($this->memberGroups($request)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            // a group is optional, but if given I must be an approved member of it
            'study_group_id' => ['nullable', Rule::exists('group_members', 'study_group_id')
                ->where('user_id', $user->id)->where('status', GroupMember::APPROVED)],
        ]);

        $user->tasks()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Task added.']);

        return back();
    }

    /** Tick / untick a task. */
    public function toggle(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->user_id === $request->user()->id, 403);

        $task->update(['completed_at' => $task->isDone() ? null : now()]);

        return back();
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->user_id === $request->user()->id, 403);

        $task->delete();

        return back();
    }
}
