<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Extra study info for a student. The allowed choices live here as
 * constants so the validation, the database defaults and the React
 * dropdowns all use the same lists.
 */
#[Fillable(['bio', 'skill_level', 'study_goal', 'study_mode'])]
class StudentProfile extends Model
{
    public const SKILL_LEVELS = ['Beginner', 'Intermediate', 'Advanced'];

    public const STUDY_GOALS = ['Pass Exam', 'Build Projects', 'Improve Grades', 'Deep Understanding'];

    public const STUDY_MODES = ['Online', 'In-person', 'Hybrid'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
