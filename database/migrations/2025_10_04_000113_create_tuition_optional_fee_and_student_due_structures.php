<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /**
         * Pivot: grade-level optional fees attached to a tuition row
         * (Tuition ↔ OptionalFee)
         */
        Schema::create('tuition_optional_fee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tuition_id')
                ->constrained('tuitions')
                ->cascadeOnDelete();
            $table->foreignId('optional_fee_id')
                ->constrained('optional_fees')
                ->cascadeOnDelete();

            $table->unique(['tuition_id', 'optional_fee_id']);
            $table->timestamps();
        });

        /**
         * Pivot: per-student optional fees
         * (OptionalFee ↔ Student)
         */
        Schema::create('optional_fee_student', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('optional_fee_id');
            $table->char('student_id', 12); // FK to students.lrn

            $table->timestamps();

            $table->foreign('optional_fee_id')
                ->references('id')
                ->on('optional_fees')
                ->cascadeOnDelete();

            $table->foreign('student_id')
                ->references('lrn')
                ->on('students')
                ->cascadeOnDelete();

            $table->unique(['optional_fee_id', 'student_id'], 'optional_fee_student_unique');
        });

        /**
         * Extra per-student totals used by your Students page
         */
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 's_optional_total')) {
                $table->decimal('s_optional_total', 10, 2)
                    ->default(0)
                    ->after('s_tuition_sum');
            }
            if (!Schema::hasColumn('students', 's_total_due')) {
                $table->decimal('s_total_due', 10, 2)
                    ->default(0)
                    ->after('s_optional_total');
            }
        });
    }

    public function down(): void
    {
        // Drop student columns only if they exist
        if (Schema::hasColumn('students', 's_total_due') ||
            Schema::hasColumn('students', 's_optional_total')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 's_total_due')) {
                    $table->dropColumn('s_total_due');
                }
                if (Schema::hasColumn('students', 's_optional_total')) {
                    $table->dropColumn('s_optional_total');
                }
            });
        }

        Schema::dropIfExists('optional_fee_student');
        Schema::dropIfExists('tuition_optional_fee');
    }
};
