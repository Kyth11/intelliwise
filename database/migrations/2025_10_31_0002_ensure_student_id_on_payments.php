<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Ensure column exists
        if (! Schema::hasColumn('payments', 'student_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->char('student_id', 12)->nullable()->after('id');
            });
        }

        // 2) Ensure FK exists (MySQL will auto-create an index if needed)
        $fkExists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'payments'
              AND COLUMN_NAME = 'student_id'
              AND REFERENCED_TABLE_NAME = 'students'
            LIMIT 1
        ");

        if (! $fkExists) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('student_id', 'payments_student_id_fk')
                      ->references('lrn')->on('students')
                      ->cascadeOnDelete()
                      ->cascadeOnUpdate();
            });
        }
    }
};
