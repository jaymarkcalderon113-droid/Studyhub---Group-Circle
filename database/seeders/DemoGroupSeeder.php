<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\GroupMember;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo students and groups so "Find Groups" has something to match.
 * Log in as mark@studyhub.test / lea@studyhub.test / john@studyhub.test
 * (password: "password") to approve join requests as a group owner.
 * Safe to run more than once.
 */
class DemoGroupSeeder extends Seeder
{
    public function run(): void
    {
        $owners = [];
        foreach (['mark' => 'Mark Reyes', 'lea' => 'Lea Tan', 'john' => 'John Diaz'] as $key => $name) {
            $user = User::firstOrCreate(
                ['email' => "{$key}@studyhub.test"],
                ['name' => $name, 'password' => 'password'],
            );
            $user->forceFill(['role' => UserRole::Student, 'email_verified_at' => now()])->save();
            $owners[$key] = $user;
        }

        // [owner, name, subject, level, goal, mode, max members]
        $groups = [
            ['mark', 'Math Study Group', 'Math', 'Intermediate', 'Pass Exam', 'Online', 4],
            ['lea', 'Programming Group', 'Programming', 'Beginner', 'Build Projects', 'Online', 4],
            ['john', 'Science Group', 'Science', 'Beginner', 'Improve Grades', 'Hybrid', 5],
            ['mark', 'English Study Group', 'English', 'Advanced', 'Deep Understanding', 'In-person', 6],
            ['lea', 'Chemistry Group', 'Chemistry', 'Intermediate', 'Pass Exam', 'Online', 5],
        ];

        foreach ($groups as [$ownerKey, $name, $subject, $level, $goal, $mode, $max]) {
            $owner = $owners[$ownerKey];

            $group = StudyGroup::firstOrCreate(
                ['name' => $name],
                [
                    'owner_id' => $owner->id,
                    'subject_id' => Subject::where('name', $subject)->value('id'),
                    'skill_level' => $level,
                    'study_goal' => $goal,
                    'study_mode' => $mode,
                    'max_members' => $max,
                ],
            );

            $group->memberships()->firstOrCreate(
                ['user_id' => $owner->id],
                ['role' => 'owner', 'status' => GroupMember::APPROVED],
            );
        }
    }
}
