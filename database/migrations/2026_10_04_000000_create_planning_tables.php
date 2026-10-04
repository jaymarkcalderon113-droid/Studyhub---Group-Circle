<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2D
 *  study_sessions = PLANNED group sessions shown on the calendar
 *                   (named study_sessions because "sessions" is Laravel's login-session table)
 *  tasks          = a student's personal checklist (can be tagged with a group)
 *  study_logs     = study time a student recorded (powers Progress)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title', 100);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('location', 100)->nullable();
            $table->timestamps();
            $table->index(['study_group_id', 'starts_at']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 120);
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable(); // null = still pending
            $table->timestamps();
        });

        Schema::create('study_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_group_id')->nullable()->constrained()->nullOnDelete();
            $table->date('studied_on');
            $table->unsignedSmallInteger('minutes');
            $table->string('topic', 100)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'studied_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_logs');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('study_sessions');
    }
};
