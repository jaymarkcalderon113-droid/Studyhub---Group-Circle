<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A study group. The table is called "study_groups" (not "groups") because
 * GROUPS is a reserved word in MySQL 8 and would cause SQL errors.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->text('description')->nullable();
            $table->string('skill_level', 20);
            $table->string('study_goal', 40);
            $table->string('study_mode', 20);
            $table->unsignedTinyInteger('max_members')->default(5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_groups');
    }
};
