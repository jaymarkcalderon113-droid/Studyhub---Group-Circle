<?php

use App\Models\User;

test('a student cannot open admin pages and is sent to the student dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin/dashboard')
        ->assertRedirect('/dashboard');
});

test('an admin is redirected away from student pages to the admin dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/dashboard')
        ->assertRedirect('/admin/dashboard');
});

test('an admin can open the admin dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/dashboard')
        ->assertOk();
});

test('admins are sent to the admin dashboard after login', function () {
    $admin = User::factory()->admin()->create();

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect('/admin/dashboard');
});

test('sign up always creates a student, even if role is posted', function () {
    $this->post(route('register.store'), [
        'name' => 'Sneaky',
        'email' => 'sneaky@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ])->assertRedirect('/dashboard');

    expect(User::where('email', 'sneaky@example.com')->first()->role->value)->toBe('student');
});
