<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_curriculum_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum', function (Blueprint $table) {
            // PK
            $table->id();

            // Name of the curriculum (e.g. "Grade 1 K–12", "STEAM Grade 7")
            $table->string('curriculum_name');

            // Single school year (matches Blade: $result->schoolyr_id, join to schoolyrs.school_year)
            $table->foreignId('schoolyr_id')
                ->nullable()
                ->constrained('schoolyrs')
                ->nullOnDelete();

            // Grade level (matches Blade: $result->grade_id, join to gradelvls.grade_level)
            $table->foreignId('grade_id')
                ->nullable()
                ->constrained('gradelvls')
                ->nullOnDelete();

            // Soft flags
            $table->tinyInteger('deleted')
                ->default(0)
                ->comment('0 = not deleted, 1 = soft-deleted record');

            // Active / inactive flag (used by selector in Blade)
            $table->tinyInteger('status')
                ->default(1)
                ->comment('1 = active, 0 = inactive');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum');
    }
};
