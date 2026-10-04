<?php

use App\Models\GroupMember;
use App\Models\StudyGroup;
use App\Models\StudyLog;
use App\Models\Subject;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\SubjectSeeder;

beforeEach(fn () => $this->seed(SubjectSeeder::class));

function planGroup(User $owner, array $members = []): StudyGroup
{
    $group = StudyGroup::create([
        'owner_id' => $owner->id, 'subject_id' => Subject::first()->id, 'name' => 'Plan Crew',
        'skill_level' => 'Beginner', 'study_goal' => 'Pass Exam', 'study_mode' => 'Online', 'max_members' => 5,
    ]);
    $group->memberships()->create(['user_id' => $owner->id, 'role' => 'owner', 'status' => GroupMember::APPROVED]);
    foreach ($members as $m) {
        $group->memberships()->create(['user_id' => $m->id, 'role' => 'member', 'status' => GroupMember::APPROVED]);
    }

    return $group;
}

test('a member can schedule a session and see it in the right month', function () {
    $owner = User::factory()->create();
    $group = planGroup($owner);

    $this->actingAs($owner)->post("/groups/{$group->id}/sessions", [
        'title' => 'Chapter 4', 'date' => '2026-10-15', 'start_time' => '10:00', 'end_time' => '12:00',
    ])->assertRedirect();

    $this->actingAs($owner)->get('/schedule?month=2026-10')
        ->assertInertia(fn ($page) => $page->has('sessions', 1)->where('sessions.0.day', 15)->where('calendar.label', 'October 2026'));

    $this->actingAs($owner)->get('/schedule?month=2026-11')
        ->assertInertia(fn ($page) => $page->has('sessions', 0));
});

test('sessions need an end time after the start and a member', function () {
    $owner = User::factory()->create();
    $group = planGroup($owner);

    $this->actingAs($owner)->post("/groups/{$group->id}/sessions", [
        'title' => 'Bad', 'date' => '2026-10-15', 'start_time' => '12:00', 'end_time' => '10:00',
    ])->assertSessionHasErrors('end_time');

    $this->actingAs(User::factory()->create())->post("/groups/{$group->id}/sessions", [
        'title' => 'Sneaky', 'date' => '2026-10-15', 'start_time' => '10:00', 'end_time' => '11:00',
    ])->assertForbidden();
});

test('tasks: add, tick, delete and nobody else can touch them', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($me)->post('/tasks', ['title' => 'Read chapter', 'due_date' => '2026-10-20'])->assertRedirect();
    $task = Task::first();

    $this->actingAs($other)->patch("/tasks/{$task->id}/toggle")->assertForbidden();
    $this->actingAs($other)->delete("/tasks/{$task->id}")->assertForbidden();

    $this->actingAs($me)->patch("/tasks/{$task->id}/toggle")->assertRedirect();
    expect($task->fresh()->isDone())->toBeTrue();

    $this->actingAs($me)->delete("/tasks/{$task->id}")->assertRedirect();
    expect(Task::count())->toBe(0);
});

test('a task can only be tagged with a group I belong to', function () {
    $me = User::factory()->create();
    $foreign = planGroup(User::factory()->create());

    $this->actingAs($me)->post('/tasks', ['title' => 'x', 'study_group_id' => $foreign->id])
        ->assertSessionHasErrors('study_group_id');
});

test('logging study time updates the weekly total', function () {
    $me = User::factory()->create();

    $this->actingAs($me)->post('/progress', ['studied_on' => today()->format('Y-m-d'), 'minutes' => 90, 'topic' => 'Integrals'])->assertRedirect();
    $this->actingAs($me)->post('/progress', ['studied_on' => today()->subDays(2)->format('Y-m-d'), 'minutes' => 30])->assertRedirect();

    $this->actingAs($me)->get('/progress')
        ->assertInertia(fn ($page) => $page->where('totalHours', 2)->has('chart', 7)->has('logs', 2));
});

test('study time cannot be logged in the future or for a stranger group', function () {
    $me = User::factory()->create();

    $this->actingAs($me)->post('/progress', ['studied_on' => today()->addDay()->format('Y-m-d'), 'minutes' => 30])
        ->assertSessionHasErrors('studied_on');

    $this->actingAs($me)->post('/progress', ['studied_on' => today()->format('Y-m-d'), 'minutes' => 30, 'study_group_id' => planGroup(User::factory()->create())->id])
        ->assertSessionHasErrors('study_group_id');

    expect(StudyLog::count())->toBe(0);
});
