<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            // UI uses Monday–Friday only
            $table->enum('day', [
                'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'
            ]);

            // Stored as TIME (HH:MM[:SS]) in MySQL; form uses HH:MM
            $table->time('class_start');
            $table->time('class_end');

            // Core relations
            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('subject_id');   // from entries[][subject_id]
            $table->unsignedBigInteger('gradelvl_id');  // from top Grade Level select

            // String FK to schoolyrs.school_year (YYYY-YYYY), set from active school year in controller
            $table->string('school_year', 9)->collation('utf8mb4_unicode_ci')->nullable();

            // Foreign keys
            $table->foreign('faculty_id')
                ->references('id')->on('faculties')
                ->cascadeOnDelete();

            $table->foreign('subject_id')
                ->references('id')->on('subjects')
                ->cascadeOnDelete();

            $table->foreign('gradelvl_id')
                ->references('id')->on('gradelvls')
                ->cascadeOnDelete();

            $table->foreign('school_year')
                ->references('school_year')->on('schoolyrs')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            // Helpful indexes
            $table->index(['day', 'class_start']);
            $table->index(['school_year']);
            $table->index(['faculty_id', 'gradelvl_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
