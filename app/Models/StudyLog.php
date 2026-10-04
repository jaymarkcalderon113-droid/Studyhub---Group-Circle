<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Study time a student recorded (minutes studied on a date). */
#[Fillable(['user_id', 'study_group_id', 'studied_on', 'minutes', 'topic'])]
class StudyLog extends Model
{
    protected function casts(): array
    {
        return ['studied_on' => 'date'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }
}
