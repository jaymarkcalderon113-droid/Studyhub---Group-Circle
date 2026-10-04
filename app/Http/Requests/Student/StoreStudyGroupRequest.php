<?php

namespace App\Http\Requests\Student;

use App\Models\StudentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates the "Create Group" form. */
class StoreStudyGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route already protected by auth + role:student
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:300'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'skill_level' => ['required', Rule::in(StudentProfile::SKILL_LEVELS)],
            'study_goal' => ['required', Rule::in(StudentProfile::STUDY_GOALS)],
            'study_mode' => ['required', Rule::in(StudentProfile::STUDY_MODES)],
            'max_members' => ['required', 'integer', 'between:2,10'],
        ];
    }
}
