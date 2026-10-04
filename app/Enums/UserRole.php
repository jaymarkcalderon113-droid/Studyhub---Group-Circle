<?php

namespace App\Enums;

/**
 * The two roles in StudyHub. Backed by a string so it is stored as
 * "student" / "admin" in MySQL and sent to React as the same string.
 */
enum UserRole: string
{
    case Student = 'student';
    case Admin = 'admin';

    /** Where this role lands after login / registration. */
    public function homePath(): string
    {
        return match ($this) {
            self::Admin => '/admin/dashboard',
            self::Student => '/dashboard',
        };
    }
}
