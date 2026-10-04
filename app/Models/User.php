<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property UserRole $role
 * @property \Illuminate\Support\Carbon|null $suspended_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'suspended_at' => 'datetime',
        ];
    }

    /** Convenience check used in policies / controllers. */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** The student's study profile (created the first time they save it). */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /** Subjects this student picked. */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class);
    }

    /** Groups this student is an approved member of (including groups they own). */
    public function studyGroups(): BelongsToMany
    {
        return $this->belongsToMany(StudyGroup::class, 'group_members', 'user_id', 'study_group_id')
            ->wherePivot('status', GroupMember::APPROVED);
    }

    /** This student's personal checklist. */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** Study time this student recorded. */
    public function studyLogs(): HasMany
    {
        return $this->hasMany(StudyLog::class);
    }

    /** Alerts for this student (see the Notifications page). */
    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }
}
