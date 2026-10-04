<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One student's link to one group (a join request or a real membership). */
#[Fillable(['study_group_id', 'user_id', 'role', 'status'])]
class GroupMember extends Model
{
    public const APPROVED = 'approved';

    public const PENDING = 'pending';

    public function group(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
