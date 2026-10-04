<?php

use App\Models\GroupMember;
use App\Models\GroupRating;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\SubjectSeeder;

beforeEach(fn () => $this->seed(SubjectSeeder::class));

function rateGroup(User $owner, array $members = []): StudyGroup
{
    $group = StudyGroup::create([
        'owner_id' => $owner->id, 'subject_id' => Subject::first()->id, 'name' => 'Rate Crew',
        'skill_level' => 'Beginner', 'study_goal' => 'Pass Exam', 'study_mode' => 'Online', 'max_members' => 5,
    ]);
    $group->memberships()->create(['user_id' => $owner->id, 'role' => 'owner', 'status' => GroupMember::APPROVED]);
    foreach ($members as $m) {
        $group->memberships()->create(['user_id' => $m->id, 'role' => 'member', 'status' => GroupMember::APPROVED]);
    }

    return $group;
}

test('a member can rate, and rating again updates instead of duplicating', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $group = rateGroup($owner, [$member]);

    $this->actingAs($member)->post("/groups/{$group->id}/ratings", ['stars' => 4, 'comment' => 'Great'])->assertRedirect();
    $this->actingAs($member)->post("/groups/{$group->id}/ratings", ['stars' => 5])->assertRedirect();

    expect(GroupRating::count())->toBe(1)->and(GroupRating::first()->stars)->toBe(5);
});

test('owners, outsiders and invalid stars cannot rate', function () {
    $owner = User::factory()->create();
    $group = rateGroup($owner);

    $this->actingAs($owner)->post("/groups/{$group->id}/ratings", ['stars' => 5])->assertForbidden();
    $this->actingAs(User::factory()->create())->post("/groups/{$group->id}/ratings", ['stars' => 5])->assertForbidden();

    $member = User::factory()->create();
    $group->memberships()->create(['user_id' => $member->id, 'role' => 'member', 'status' => GroupMember::APPROVED]);
    $this->actingAs($member)->post("/groups/{$group->id}/ratings", ['stars' => 9])->assertSessionHasErrors('stars');
});

test('the ratings page shows the average', function () {
    $owner = User::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();
    $group = rateGroup($owner, [$a, $b]);
    GroupRating::create(['study_group_id' => $group->id, 'user_id' => $a->id, 'stars' => 4]);
    GroupRating::create(['study_group_id' => $group->id, 'user_id' => $b->id, 'stars' => 5]);

    $this->actingAs($owner)->get('/ratings')
        ->assertInertia(fn ($page) => $page->where('groups.0.average', 4.5)->where('groups.0.count', 2));
});

test('a join request notifies the owner, and approval notifies the student', function () {
    $owner = User::factory()->create();
    $student = User::factory()->create();
    $group = rateGroup($owner);

    $this->actingAs($student)->post("/groups/{$group->id}/join");
    expect(UserNotification::where('user_id', $owner->id)->where('type', 'join_request')->count())->toBe(1);

    $request = GroupMember::where('user_id', $student->id)->first();
    $this->actingAs($owner)->patch("/groups/{$group->id}/members/{$request->id}", ['action' => 'approve']);
    expect(UserNotification::where('user_id', $student->id)->where('type', 'approved')->count())->toBe(1);
});

test('chat alerts are grouped: many messages, one unread alert', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $group = rateGroup($owner, [$member]);

    foreach (['one', 'two', 'three'] as $text) {
        $this->actingAs($owner)->post("/groups/{$group->id}/messages", ['body' => $text]);
    }

    expect(UserNotification::where('user_id', $member->id)->where('type', 'message')->count())->toBe(1)
        ->and(UserNotification::where('user_id', $owner->id)->count())->toBe(0); // no alert for the sender
});

test('notifications can be read, and only by their owner', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();
    $n = UserNotification::create(['user_id' => $me->id, 'type' => 'note', 'title' => 'Hi']);

    $this->actingAs($other)->patch("/notifications/{$n->id}/read")->assertForbidden();
    $this->actingAs($me)->patch("/notifications/{$n->id}/read")->assertRedirect();
    expect($n->fresh()->read_at)->not->toBeNull();

    UserNotification::create(['user_id' => $me->id, 'type' => 'note', 'title' => 'Two']);
    $this->actingAs($me)->post('/notifications/read-all')->assertRedirect();
    expect($me->userNotifications()->whereNull('read_at')->count())->toBe(0);
});

test('the dashboard shows real numbers', function () {
    $owner = User::factory()->create();
    $group = rateGroup($owner);
    $owner->tasks()->create(['title' => 'Read']);

    $this->actingAs($owner)->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('stats.groups', 1)->where('stats.tasksDue', 1)->where('stats.rating', null));
});
