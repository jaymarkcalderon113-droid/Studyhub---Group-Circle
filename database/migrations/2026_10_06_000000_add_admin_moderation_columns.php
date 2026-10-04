<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2F (admin tools)
 *  users.suspended_at        = set when an admin suspends the account (null = active)
 *  study_groups.flagged_at   = set when an admin flags a group for review (null = normal)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('role');
        });

        Schema::table('study_groups', function (Blueprint $table) {
            $table->timestamp('flagged_at')->nullable()->after('max_members');
        });
    }

    public function down(): void
    {
        Schema::table('study_groups', fn (Blueprint $table) => $table->dropColumn('flagged_at'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('suspended_at'));
    }
};
