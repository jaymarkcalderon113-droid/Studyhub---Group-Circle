<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateStudentProfileRequest;
use App\Models\StudentProfile;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** "My Profile": subjects, skill level, study goal, preference. */
class StudentProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->studentProfile;

        return Inertia::render('my-profile', [
            // Current saved values (or sensible defaults for a brand-new student)
            'profile' => [
                'bio' => $profile?->bio ?? '',
                'skill_level' => $profile?->skill_level ?? 'Beginner',
                'study_goal' => $profile?->study_goal ?? 'Pass Exam',
                'study_mode' => $profile?->study_mode ?? 'Online',
                'subjects' => $user->subjects()->pluck('subjects.id'),
            ],
            // Choices for the form
            'subjectOptions' => Subject::orderBy('name')->get(['id', 'name']),
            'skillLevels' => StudentProfile::SKILL_LEVELS,
            'studyGoals' => StudentProfile::STUDY_GOALS,
            'studyModes' => StudentProfile::STUDY_MODES,
        ]);
    }

    public function update(UpdateStudentProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // updateOrCreate: creates the profile row the first time, updates it after.
        $user->studentProfile()->updateOrCreate([], [
            'bio' => $data['bio'] ?? null,
            'skill_level' => $data['skill_level'],
            'study_goal' => $data['study_goal'],
            'study_mode' => $data['study_mode'],
        ]);

        // sync() makes the pivot table match exactly the ids the student picked.
        $user->subjects()->sync($data['subjects']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profile saved.']);

        return to_route('my-profile');
    }
}
