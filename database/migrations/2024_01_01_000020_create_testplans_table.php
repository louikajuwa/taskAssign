<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testplans', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->text('case_information');
            $table->string('version_type');
            $table->string('version_number')->nullable();
            $table->string('type_of_status')->nullable();
            $table->string('priority')->nullable();
            $table->string('main_status')->default('Open');
            $table->json('item_data_file')->nullable();
            $table->text('assign_to_dev_ids')->nullable();
            $table->text('generic_tester_ids')->nullable();
            $table->text('sandbox_tester_ids')->nullable();
            $table->text('alpha_tester_ids')->nullable();
            $table->text('beta_tester_ids')->nullable();
            $table->text('live_tester_ids')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testplans');
    }
};
