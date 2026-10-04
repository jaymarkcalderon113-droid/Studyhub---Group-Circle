<?php

namespace App\Http\Requests\Student;

use App\Models\StudentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates the "My Profile" form. Never trust the browser: check everything here. */
class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route is already protected by auth + role:student
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bio' => ['nullable', 'string', 'max:500'],
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*' => ['integer', 'distinct', 'exists:subjects,id'],
            'skill_level' => ['required', Rule::in(StudentProfile::SKILL_LEVELS)],
            'study_goal' => ['required', Rule::in(StudentProfile::STUDY_GOALS)],
            'study_mode' => ['required', Rule::in(StudentProfile::STUDY_MODES)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['subjects.required' => 'Pick at least one subject.', 'subjects.min' => 'Pick at least one subject.'];
    }
}
