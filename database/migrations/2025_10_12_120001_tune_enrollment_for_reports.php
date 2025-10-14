<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('enrollment', function (Blueprint $table) {
            // Ensure finance columns exist (no-op if already there)
            if (!Schema::hasColumn('enrollment', 'base_tuition'))    $table->decimal('base_tuition', 12, 2)->default(0)->after('is_active');
            if (!Schema::hasColumn('enrollment', 'optional_total'))  $table->decimal('optional_total', 12, 2)->default(0)->after('base_tuition');
            if (!Schema::hasColumn('enrollment', 'total_due'))       $table->decimal('total_due', 12, 2)->default(0)->after('optional_total');
            if (!Schema::hasColumn('enrollment', 'paid_to_date'))    $table->decimal('paid_to_date', 12, 2)->default(0)->after('total_due');
            if (!Schema::hasColumn('enrollment', 'balance_cached'))  $table->decimal('balance_cached', 12, 2)->default(0)->after('paid_to_date');

            // Helpful indexes for the report filters
            $table->index(['schoolyr_id', 'gradelvl_id'], 'enr_sy_grade_idx');
            $table->index(['status', 'payment_status'], 'enr_status_idx');
            $table->index(['student_id', 'tuition_id'], 'enr_student_tuition_idx');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment', function (Blueprint $table) {
            // Keep indexes by default; if you really want to drop:
            try { $table->dropIndex('enr_sy_grade_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('enr_status_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('enr_student_tuition_idx'); } catch (\Throwable $e) {}
        });
    }
};
