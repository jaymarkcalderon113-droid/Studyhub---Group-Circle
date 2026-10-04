<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** One alert for one student. */
#[Fillable(['user_id', 'study_group_id', 'type', 'title', 'body', 'link', 'read_at'])]
class UserNotification extends Model
{
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
