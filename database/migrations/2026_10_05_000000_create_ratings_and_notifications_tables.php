<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2E
 *  group_ratings      = a member's 1-5 star rating (+ comment) of a group; one per member per group
 *  user_notifications = alerts shown on the Notifications page (read_at null = unread)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stars'); // 1-5
            $table->string('comment', 300)->nullable();
            $table->timestamps();

            $table->unique(['study_group_id', 'user_id']); // changing your rating updates the same row
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 30);             // message | join_request | approved | session | note | file | rating
            $table->string('title');
            $table->string('body')->nullable();
            $table->string('link')->nullable();     // where clicking it goes, e.g. /messages?group=3
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('group_ratings');
    }
};
