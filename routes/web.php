<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminGroupController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\GroupChatController;
use App\Http\Controllers\Student\GroupLibraryController;
use App\Http\Controllers\Student\NotificationController;
use App\Http\Controllers\Student\ProgressController;
use App\Http\Controllers\Student\RatingController;
use App\Http\Controllers\Student\ScheduleController;
use App\Http\Controllers\Student\StudentProfileController;
use App\Http\Controllers\Student\StudyGroupController;
use App\Http\Controllers\Student\TaskController;
use Illuminate\Support\Facades\Route;

// Public landing page (React: resources/js/pages/welcome.tsx)
Route::inertia('/', 'welcome')->name('home');

/*
|--------------------------------------------------------------------------
| STUDENT area  (role:student)
|--------------------------------------------------------------------------
| Phase 1: these pages render React with mock data. Phase 2 will replace
| Route::inertia() with real controllers.
*/
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('my-profile', [StudentProfileController::class, 'edit'])->name('my-profile');
    Route::put('my-profile', [StudentProfileController::class, 'update'])->name('my-profile.update');
    // Phase 2B: groups
    Route::get('findgroups', [StudyGroupController::class, 'find'])->name('findgroups');
    Route::get('mygroups', [StudyGroupController::class, 'index'])->name('mygroups');
    Route::post('mygroups', [StudyGroupController::class, 'store'])->name('mygroups.store');
    Route::post('groups/{group}/join', [StudyGroupController::class, 'join'])->name('groups.join');
    Route::patch('groups/{group}/members/{member}', [StudyGroupController::class, 'respond'])->name('groups.respond');
    Route::delete('groups/{group}/leave', [StudyGroupController::class, 'leave'])->name('groups.leave');

    // Phase 2C: chat, notes, files (all members-only inside the controllers)
    Route::get('messages', [GroupChatController::class, 'index'])->name('messages');
    Route::post('groups/{group}/messages', [GroupChatController::class, 'store'])->name('groups.messages.store');

    Route::get('notes', [GroupLibraryController::class, 'index'])->name('notes');
    Route::post('groups/{group}/notes', [GroupLibraryController::class, 'storeNote'])->name('groups.notes.store');
    Route::delete('groups/{group}/notes/{note}', [GroupLibraryController::class, 'destroyNote'])->name('groups.notes.destroy');
    Route::post('groups/{group}/files', [GroupLibraryController::class, 'storeFile'])->name('groups.files.store');
    Route::get('groups/{group}/files/{file}', [GroupLibraryController::class, 'downloadFile'])->name('groups.files.download');
    Route::delete('groups/{group}/files/{file}', [GroupLibraryController::class, 'destroyFile'])->name('groups.files.destroy');

    // Phase 2E: dashboard, ratings, notifications
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('ratings', [RatingController::class, 'index'])->name('ratings');
    Route::post('groups/{group}/ratings', [RatingController::class, 'store'])->name('groups.ratings.store');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Phase 2D: schedule, tasks, progress
    Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule');
    Route::post('groups/{group}/sessions', [ScheduleController::class, 'store'])->name('groups.sessions.store');
    Route::delete('groups/{group}/sessions/{studySession}', [ScheduleController::class, 'destroy'])->name('groups.sessions.destroy');

    Route::get('tasks', [TaskController::class, 'index'])->name('tasks');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    Route::get('progress', [ProgressController::class, 'index'])->name('progress');
    Route::post('progress', [ProgressController::class, 'store'])->name('progress.store');
    Route::delete('progress/{log}', [ProgressController::class, 'destroy'])->name('progress.destroy');
});

/*
|--------------------------------------------------------------------------
| ADMIN area  (role:admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', AdminDashboardController::class)->name('dashboard');

    Route::get('users', [AdminUserController::class, 'index'])->name('users');
    Route::patch('users/{user}/suspend', [AdminUserController::class, 'toggleSuspend'])->name('users.suspend');

    Route::get('groups', [AdminGroupController::class, 'index'])->name('groups');
    Route::patch('groups/{group}/flag', [AdminGroupController::class, 'toggleFlag'])->name('groups.flag');
    Route::delete('groups/{group}', [AdminGroupController::class, 'destroy'])->name('groups.destroy');
});

require __DIR__.'/settings.php';
