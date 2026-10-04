<?php

use App\Models\GroupMember;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\SubjectSeeder;

beforeEach(fn () => $this->seed(SubjectSeeder::class));

function adminGroup(User $owner): StudyGroup
{
    $group = StudyGroup::create([
        'owner_id' => $owner->id, 'subject_id' => Subject::first()->id, 'name' => 'Mod Crew',
        'skill_level' => 'Beginner', 'study_goal' => 'Pass Exam', 'study_mode' => 'Online', 'max_members' => 5,
    ]);
    $group->memberships()->create(['user_id' => $owner->id, 'role' => 'owner', 'status' => GroupMember::APPROVED]);

    return $group;
}

test('students cannot reach any admin page or action', function () {
    $student = User::factory()->create();
    $other = User::factory()->create();
    $group = adminGroup($other);

    $this->actingAs($student)->get('/admin/users')->assertRedirect('/dashboard');
    $this->actingAs($student)->patch("/admin/users/{$other->id}/suspend")->assertRedirect('/dashboard');
    $this->actingAs($student)->delete("/admin/groups/{$group->id}")->assertRedirect('/dashboard');
    expect(StudyGroup::count())->toBe(1);
});

test('the admin dashboard and lists load with real data', function () {
    $admin = User::factory()->admin()->create();
    adminGroup(User::factory()->create());

    $this->actingAs($admin)->get('/admin/dashboard')
        ->assertInertia(fn ($page) => $page->where('stats.groups', 1)->where('stats.students', 1));
    $this->actingAs($admin)->get('/admin/users?q='.urlencode($admin->email))
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
    $this->actingAs($admin)->get('/admin/groups')
        ->assertInertia(fn ($page) => $page->has('groups.data', 1));
});

test('an admin can suspend and reactivate a student', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $this->actingAs($admin)->patch("/admin/users/{$student->id}/suspend")->assertRedirect();
    expect($student->fresh()->isSuspended())->toBeTrue();

    $this->actingAs($admin)->patch("/admin/users/{$student->id}/suspend")->assertRedirect();
    expect($student->fresh()->isSuspended())->toBeFalse();
});

test('admins cannot be suspended, not even by themselves', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/admin/users/{$admin->id}/suspend")->assertForbidden();
    $this->actingAs($admin)->patch("/admin/users/{$other->id}/suspend")->assertForbidden();
});

test('a suspended student cannot log in', function () {
    $student = User::factory()->create();
    $student->forceFill(['suspended_at' => now()])->save();

    $this->post(route('login.store'), ['email' => $student->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('a student suspended while logged in is signed out on the next request', function () {
    $student = User::factory()->create();
    $this->actingAs($student)->get('/dashboard')->assertOk();

    $student->forceFill(['suspended_at' => now()])->save();

    $this->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});

test('an admin can flag and delete a group', function () {
    $admin = User::factory()->admin()->create();
    $group = adminGroup(User::factory()->create());

    $this->actingAs($admin)->patch("/admin/groups/{$group->id}/flag")->assertRedirect();
    expect($group->fresh()->flagged_at)->not->toBeNull();

    $this->actingAs($admin)->delete("/admin/groups/{$group->id}")->assertRedirect();
    expect(StudyGroup::count())->toBe(0)->and(GroupMember::count())->toBe(0);
});

test('logging in sends each role to its own dashboard', function () {
    $admin = User::factory()->admin()->create();
    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/admin/dashboard');
    auth()->logout();

    $student = User::factory()->create();
    $this->post(route('login.store'), ['email' => $student->email, 'password' => 'password'])->assertRedirect('/dashboard');
});
