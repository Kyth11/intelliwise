<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tuitions', function (Blueprint $table) {
            $table->id();

            // Grade level stored as text (keeps your existing blades/controllers simple)
            $table->string('grade_level');

            // TUITION (split)
            $table->decimal('tuition_monthly', 10, 2)->default(0);
            $table->decimal('tuition_yearly', 10, 2)->default(0);  // should be monthly * 10

            // FEES (split)
            $table->decimal('misc_monthly', 10, 2)->nullable();
            $table->decimal('misc_yearly', 10, 2)->nullable();     // should mirror misc_monthly * 10

            $table->string('books_desc')->nullable();
            $table->decimal('books_amount', 10, 2)->nullable();

            // Computed total (tuition_yearly + misc_yearly + books_amount + grade-level optional fees)
            $table->decimal('total_yearly', 10, 2)->default(0);

            // Keep school year as TEXT (YYYY-YYYY) to match your controllers and views today
            $table->string('school_year', 9)->nullable(); // e.g. 2025-2026

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuitions');
    }
};
