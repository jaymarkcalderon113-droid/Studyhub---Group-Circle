<?php

use App\Models\Subject;
use App\Models\User;
use Database\Seeders\SubjectSeeder;

beforeEach(fn () => $this->seed(SubjectSeeder::class));

test('a student can save their profile and subjects', function () {
    $user = User::factory()->create();
    $ids = Subject::whereIn('name', ['Math', 'Science'])->pluck('id')->all();

    $this->actingAs($user)->put('/my-profile', [
        'bio' => 'Night owl',
        'subjects' => $ids,
        'skill_level' => 'Intermediate',
        'study_goal' => 'Build Projects',
        'study_mode' => 'Hybrid',
    ])->assertRedirect('/my-profile');

    expect($user->fresh()->studentProfile->study_goal)->toBe('Build Projects')
        ->and($user->subjects()->count())->toBe(2);
});

test('the profile needs at least one subject and valid choices', function () {
    $this->actingAs(User::factory()->create())
        ->put('/my-profile', ['subjects' => [], 'skill_level' => 'Expert', 'study_goal' => 'x', 'study_mode' => 'y'])
        ->assertSessionHasErrors(['subjects', 'skill_level', 'study_goal', 'study_mode']);
});

test('admins cannot use the student profile page', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/my-profile')->assertRedirect('/admin/dashboard');
});
