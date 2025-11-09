<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    protected function indexExists(string $table, string $indexName): bool
    {
        $row = DB::selectOne("
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
            LIMIT 1
        ", [$table, $indexName]);

        return (bool) $row;
    }

    protected function ensureIndex(string $table, string $indexName, array $cols): void
    {
        if (! $this->indexExists($table, $indexName)) {
            $colsSql = implode('`,`', $cols);
            DB::statement("CREATE INDEX `{$indexName}` ON `{$table}` (`{$colsSql}`)");
        }
    }

    public function up(): void
    {
        // Finance columns (idempotent)
        Schema::table('enrollment', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollment', 'base_tuition'))   $table->decimal('base_tuition', 12, 2)->default(0)->after('is_active');
            if (!Schema::hasColumn('enrollment', 'optional_total')) $table->decimal('optional_total', 12, 2)->default(0)->after('base_tuition');
            if (!Schema::hasColumn('enrollment', 'total_due'))      $table->decimal('total_due', 12, 2)->default(0)->after('optional_total');
            if (!Schema::hasColumn('enrollment', 'paid_to_date'))   $table->decimal('paid_to_date', 12, 2)->default(0)->after('total_due');
            if (!Schema::hasColumn('enrollment', 'balance_cached')) $table->decimal('balance_cached', 12, 2)->default(0)->after('paid_to_date');
        });

        // Safe index creation (avoid duplicate/auto-FK index conflicts)
        $this->ensureIndex('enrollment', 'enr_sy_grade_idx', ['schoolyr_id', 'gradelvl_id']);
        $this->ensureIndex('enrollment', 'enr_status_idx',   ['status', 'payment_status']);
        $this->ensureIndex('enrollment', 'enr_student_tuition_idx', ['student_id', 'tuition_id']);
    }

};
