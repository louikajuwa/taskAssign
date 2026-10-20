<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('testplan_id')->constrained('testplans')->cascadeOnDelete();
            $table->tinyInteger('assignment_role_id');
            $table->foreignId('target_user_id')->constrained('users');
            $table->foreignId('author_id')->constrained('users');
            $table->text('comment');
            $table->json('comment_file')->nullable();
            $table->string('thread_id')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
