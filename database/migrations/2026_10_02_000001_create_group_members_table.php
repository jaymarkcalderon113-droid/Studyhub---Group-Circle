<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who belongs to which group.
 * status = "pending"  -> the student asked to join, the owner hasn't answered
 * status = "approved" -> the student is a member
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('member');   // owner | member
            $table->string('status', 20)->default('pending'); // pending | approved
            $table->timestamps();

            $table->unique(['study_group_id', 'user_id']); // one row per student per group
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};
