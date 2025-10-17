<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add column if missing
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'student_id')) {
                $table->unsignedBigInteger('student_id')->nullable()->after('id');
                $table->index('student_id', 'payments_student_id_idx');
            }
        });

        // 2) Add FK if not present
        // MySQL only: check information_schema to avoid duplicate FK name error
        $fkExists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'payments'
              AND COLUMN_NAME = 'student_id'
              AND REFERENCED_TABLE_NAME = 'students'
            LIMIT 1
        ");

        if (!$fkExists) {
            Schema::table('payments', function (Blueprint $table) {
                // Use explicit name to avoid clashes
                $table->foreign('student_id', 'payments_student_id_fk')
                      ->references('id')
                      ->on('students')
                      ->onDelete('cascade')
                      ->onUpdate('cascade');
            });
        }
    }

    public function down(): void
    {
        // Drop FK if exists
        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign('payments_student_id_fk');
            });
        } catch (\Throwable $e) { /* ignore */ }

        // Drop index + column if present
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'student_id')) {
                try { $table->dropIndex('payments_student_id_idx'); } catch (\Throwable $e) {}
                $table->dropColumn('student_id');
            }
        });
    }
};
