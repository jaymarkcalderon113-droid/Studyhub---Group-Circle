<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One item on a student's checklist. */
#[Fillable(['user_id', 'study_group_id', 'title', 'due_date', 'completed_at'])]
class Task extends Model
{
    protected function casts(): array
    {
        return ['due_date' => 'date', 'completed_at' => 'datetime'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function isDone(): bool
    {
        return $this->completed_at !== null;
    }
}
