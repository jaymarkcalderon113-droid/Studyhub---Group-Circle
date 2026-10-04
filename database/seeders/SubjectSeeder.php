<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /** Fills the subjects list. Safe to run repeatedly. */
    public function run(): void
    {
        foreach (['Math', 'Programming', 'Science', 'English', 'Chemistry', 'History'] as $name) {
            Subject::firstOrCreate(['name' => $name]);
        }
    }
}
