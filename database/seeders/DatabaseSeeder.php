<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Creates one admin and one demo student so you can test both roles.
     * Safe to run more than once (firstOrCreate).
     *
     * Admin:   admin@studyhub.test   / password
     * Student: student@studyhub.test / password
     */
    public function run(): void
    {
        $this->call(SubjectSeeder::class);
        $this->call(DemoGroupSeeder::class); // needs subjects first

        foreach ([
            ['Admin User', 'admin@studyhub.test', UserRole::Admin],
            ['Anna Student', 'student@studyhub.test', UserRole::Student],
        ] as [$name, $email, $role]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password'], // hashed by the model cast
            );

            // role is not mass-assignable on purpose, so set it explicitly.
            $user->forceFill(['role' => $role, 'email_verified_at' => now()])->save();
        }
    }
}
