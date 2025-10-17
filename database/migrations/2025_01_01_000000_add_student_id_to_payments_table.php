<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $fkName   = 'fk_payments_student_id';
    private string $idxName  = 'idx_payments_student_id';

    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    private function hasIndex(string $table, string $index): bool
    {
        $dbName = DB::getDatabaseName();
        $sql = "
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = ? AND table_name = ? AND index_name = ?
            LIMIT 1
        ";
        return (bool) DB::selectOne($sql, [$dbName, $table, $index]);
    }

    private function hasForeignKey(string $table, string $constraint): bool
    {
        $dbName = DB::getDatabaseName();
        $sql = "
            SELECT 1
            FROM information_schema.table_constraints
            WHERE table_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = 'FOREIGN KEY'
            LIMIT 1
        ";
        return (bool) DB::selectOne($sql, [$dbName, $table, $constraint]);
    }

    public function up(): void
    {
        // 1) Add column if missing
        if (!$this->hasColumn('payments', 'student_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('student_id')->nullable()->after('id');
            });
        }

        // 2) Add index if missing (use custom name to avoid collisions)
        if (!$this->hasIndex('payments', $this->idxName)) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('student_id', $this->idxName);
            });
        }

        // 3) Add FK if missing (use custom name to avoid collisions)
        if (!$this->hasForeignKey('payments', $this->fkName)) {
            // If another migration already created Laravel's default
            // `payments_student_id_foreign`, don't add a second FK.
            if (!$this->hasForeignKey('payments', 'payments_student_id_foreign')) {
                Schema::table('payments', function (Blueprint $table) {
                    // Pick ONE behavior you want; keeping SET NULL is common for payments
                    $table->foreign('student_id', $this->fkName)
                        ->references('id')->on('students')
                        ->onDelete('set null')->onUpdate('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        // Drop our custom FK if present
        if ($this->hasForeignKey('payments', $this->fkName)) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign($this->fkName);
            });
        }

        // (If you want to drop Laravel's default FK too, uncomment below)
        // if ($this->hasForeignKey('payments', 'payments_student_id_foreign')) {
        //     Schema::table('payments', function (Blueprint $table) {
        //         $table->dropForeign('payments_student_id_foreign');
        //     });
        // }

        // Drop our custom index if present
        if ($this->hasIndex('payments', $this->idxName)) {
            Schema::table('payments', function (Blueprint $table) use (&$indexName) {
                $table->dropIndex($this->idxName);
            });
        }

        // Do NOT drop the column here if other migrations depend on it.
        // If you absolutely need to, guard it:
        // if ($this->hasColumn('payments', 'student_id')) {
        //     Schema::table('payments', function (Blueprint $table) {
        //         $table->dropColumn('student_id');
        //     });
        // }
    }
};
