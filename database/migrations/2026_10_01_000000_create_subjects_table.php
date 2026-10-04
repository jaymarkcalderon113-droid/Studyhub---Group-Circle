<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "subjects" = the list of subjects students can pick (Math, Programming...).
 * "subject_user" = pivot table: which student picked which subjects
 * (a many-to-many relationship).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('subject_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'subject_id']); // can't pick the same subject twice
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_user');
        Schema::dropIfExists('subjects');
    }
};
