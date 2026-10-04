<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A study group created by a student. */
#[Fillable(['owner_id', 'subject_id', 'name', 'description', 'skill_level', 'study_goal', 'study_mode', 'max_members'])]
class StudyGroup extends Model
{
    protected function casts(): array
    {
        return ['flagged_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** Every membership row: pending requests AND approved members. */
    public function memberships(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    /** How many approved members the group has right now. */
    public function approvedCount(): int
    {
        return $this->memberships()->where('status', GroupMember::APPROVED)->count();
    }

    /** Approved members only, as User models. */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members', 'study_group_id', 'user_id')
            ->wherePivot('status', GroupMember::APPROVED);
    }

    /** Is this user an approved member? This is THE access check for chat, notes and files. */
    public function hasApprovedMember(User $user): bool
    {
        return $this->memberships()
            ->where('user_id', $user->id)
            ->where('status', GroupMember::APPROVED)
            ->exists();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GroupMessage::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(GroupNote::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(GroupFile::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(GroupRating::class);
    }
}
