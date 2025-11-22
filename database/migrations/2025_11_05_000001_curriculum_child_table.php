<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_curriculum_child_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_child', function (Blueprint $table) {
            $table->id();

            // Parent curriculum
            $table->foreignId('curriculum_id')
                ->constrained('curriculum')
                ->cascadeOnDelete();

            // Subject in that curriculum (subjects table already exists)
            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->tinyInteger('deleted')
                ->default(0)
                ->comment('0 = not deleted, 1 = soft-deleted in this curriculum');

            $table->tinyInteger('status')
                ->default(1)
                ->comment('1 = active, 0 = inactive');

            // Optional schedule fields (can be used later)
            $table->string('day_schedule', 255)->nullable();
            $table->time('class_start')->nullable();
            $table->time('class_end')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_child');
    }
};
