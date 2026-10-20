<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->unique(['testplan_id', 'user_id', 'role_id'], 'assignments_testplan_user_role_unique');
        });

        // testplans.created_by is already indexed by its foreign key (testplans_created_by_foreign).
        Schema::table('testplans', function (Blueprint $table) {
            $table->index('main_status', 'testplans_main_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('testplans', function (Blueprint $table) {
            $table->dropIndex('testplans_main_status_index');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropUnique('assignments_testplan_user_role_unique');
        });
    }
};
