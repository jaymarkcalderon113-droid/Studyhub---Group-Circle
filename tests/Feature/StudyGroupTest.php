<?php

use App\Models\GroupMember;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\SubjectSeeder;

beforeEach(fn () => $this->seed(SubjectSeeder::class));

/** Small helper: make a group owned by $owner with the owner as approved member. */
function makeGroup(User $owner, array $attrs = []): StudyGroup
{
    $group = StudyGroup::create($attrs + [
        'owner_id' => $owner->id,
        'subject_id' => Subject::where('name', 'Math')->value('id'),
        'name' => 'Math Crew',
        'skill_level' => 'Intermediate',
        'study_goal' => 'Pass Exam',
        'study_mode' => 'Online',
        'max_members' => 3,
    ]);
    $group->memberships()->create(['user_id' => $owner->id, 'role' => 'owner', 'status' => GroupMember::APPROVED]);

    return $group;
}

test('creating a group makes the creator the owner and first member', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/mygroups', [
        'name' => 'Calc Crew',
        'subject_id' => Subject::first()->id,
        'skill_level' => 'Beginner',
        'study_goal' => 'Pass Exam',
        'study_mode' => 'Online',
        'max_members' => 4,
    ])->assertRedirect('/mygroups');

    $group = StudyGroup::first();
    expect($group->owner_id)->toBe($user->id)
        ->and($group->approvedCount())->toBe(1);
});

test('matching ranks the best group first and hides groups I am already in', function () {
    $owner = User::factory()->create();
    $student = User::factory()->create();
    $best = makeGroup($owner, ['name' => 'Best']);
    makeGroup($owner, ['name' => 'Weak', 'skill_level' => 'Advanced', 'study_goal' => 'Build Projects', 'study_mode' => 'Hybrid']);
    makeGroup($student, ['name' => 'Mine']);

    $this->actingAs($student)
        ->get('/findgroups?'.http_build_query([
            'subjects' => [Subject::where('name', 'Math')->value('id')],
            'skill_level' => 'Intermediate', 'study_goal' => 'Pass Exam', 'study_mode' => 'Online',
        ]))
        ->assertInertia(fn ($page) => $page
            ->where('results.0.name', 'Best')
            ->where('results.0.score', 100)
            ->has('results', 2));
});

test('joining creates a pending request and the owner can approve it', function () {
    $owner = User::factory()->create();
    $student = User::factory()->create();
    $group = makeGroup($owner);

    $this->actingAs($student)->post("/groups/{$group->id}/join")->assertRedirect();

    $request = GroupMember::where('user_id', $student->id)->first();
    expect($request->status)->toBe('pending');

    $this->actingAs($owner)
        ->patch("/groups/{$group->id}/members/{$request->id}", ['action' => 'approve'])
        ->assertRedirect();

    expect($request->fresh()->status)->toBe('approved');
});

test('only the group owner can approve requests', function () {
    $owner = User::factory()->create();
    $student = User::factory()->create();
    $stranger = User::factory()->create();
    $group = makeGroup($owner);
    $request = $group->memberships()->create(['user_id' => $student->id, 'role' => 'member', 'status' => 'pending']);

    $this->actingAs($stranger)
        ->patch("/groups/{$group->id}/members/{$request->id}", ['action' => 'approve'])
        ->assertForbidden();
});

test('a full group cannot be joined', function () {
    $owner = User::factory()->create();
    $group = makeGroup($owner, ['max_members' => 2]);
    $group->memberships()->create(['user_id' => User::factory()->create()->id, 'role' => 'member', 'status' => 'approved']);

    $late = User::factory()->create();
    $this->actingAs($late)->post("/groups/{$group->id}/join");

    expect(GroupMember::where('user_id', $late->id)->exists())->toBeFalse();
});

test('the owner cannot leave their own group', function () {
    $owner = User::factory()->create();
    $group = makeGroup($owner);

    $this->actingAs($owner)->delete("/groups/{$group->id}/leave")->assertForbidden();
});
