<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to_email')->index();
            $table->string('subject');
            $table->string('type')->nullable();              // notification type (assignment_added, ...)
            $table->string('status');                        // sent | failed | skipped
            $table->text('error')->nullable();               // failure reason, if any
            $table->string('dedup_key', 64)->nullable()->index();
            $table->unsignedInteger('attempt')->default(1);
            $table->timestamps();

            // Speeds up the daily prune of old "sent" rows
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
