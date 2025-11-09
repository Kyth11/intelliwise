<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Ensure the column exists and is the right type (idempotent)
        Schema::table('tuitions', function (Blueprint $table) {
            // If the column is missing, add it. If present, make sure it's VARCHAR(9) NULL.
            if (!Schema::hasColumn('tuitions', 'school_year')) {
                $table->string('school_year', 9)->nullable()->after('total_yearly');
            } else {
                // requires doctrine/dbal
                $table->string('school_year', 9)->nullable()->change();
            }
        });

        // 2) Drop ANY existing FK on tuitions.school_year if present (regardless of its name)
        $this->dropAnyFkOnColumn('tuitions', 'school_year');

        // 3) (Re)create the FK with a stable name
        Schema::table('tuitions', function (Blueprint $table) {
            // Optional: make sure there's an index on the referencing column
            // (MySQL will auto-create one if needed, but this is harmless.)
            $table->index('school_year', 'tuitions_school_year_idx');

            $table->foreign('school_year', 'tuitions_school_year_fk')
                ->references('school_year')->on('schoolyrs') // schoolyrs.school_year must be UNIQUE
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }


    /** ---------- Helpers ---------- */

    private function dropAnyFkOnColumn(string $table, string $column): void
    {
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $column]);

        foreach ($constraints as $row) {
            $this->dropFkIfExists($table, $row->CONSTRAINT_NAME);
        }
    }

    private function dropFkIfExists(string $table, string $constraint): void
    {
        $exists = DB::selectOne("
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            LIMIT 1
        ", [$table, $constraint]);

        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($constraint) {
                $table->dropForeign($constraint);
            });
        }
    }
};
