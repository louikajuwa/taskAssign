<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('testplan_id')->constrained('testplans')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users');
            $table->foreignId('user_id')->constrained('users');
            $table->tinyInteger('role_id');
            $table->string('role_name');
            $table->integer('level')->default(0);
            $table->string('status')->default('pending');
            $table->string('what_status')->default('pending');
            $table->string('go_live')->nullable();
            $table->text('description')->nullable();
            $table->string('comment_thread_id')->nullable();
            $table->json('file_info')->nullable();
            $table->string('field_name');
            $table->timestamps();

            $table->index(['testplan_id', 'role_id']);
            $table->index(['testplan_id', 'user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
