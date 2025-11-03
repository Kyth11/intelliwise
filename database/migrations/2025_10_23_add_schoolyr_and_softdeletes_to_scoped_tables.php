<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // tables to add schoolyr_id + soft deletes
        $tables = [
            'students',
            'guardians',
            'faculties',
            'subjects',
            'tuitions',
            'optional_fees',
            'payments',
            'enrollment', // already has schoolyr_id but ensure softDeletes
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) continue;

            Schema::table($table, function (Blueprint $t) use ($table) {
                // add schoolyr_id if not present
                if (! Schema::hasColumn($table, 'schoolyr_id')) {
                    $t->unsignedBigInteger('schoolyr_id')->nullable()->after('id');
                    $t->foreign('schoolyr_id')->references('id')->on('schoolyrs')->nullOnDelete()->cascadeOnUpdate();
                }

                // add deleted_at (soft deletes)
                if (! Schema::hasColumn($table, 'deleted_at')) {
                    $t->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'students',
            'guardians',
            'faculties',
            'subjects',
            'tuitions',
            'optional_fees',
            'payments',
            'enrollment',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) continue;

            Schema::table($table, function (Blueprint $t) use ($table) {
                if (Schema::hasColumn($table, 'schoolyr_id')) {
                    // drop foreign then column
                    $sm = Schema::getConnection()->getDoctrineSchemaManager();
                    $fkList = $sm->listTableForeignKeys($table);
                    foreach ($fkList as $fk) {
                        if (in_array('schoolyr_id', $fk->getLocalColumns())) {
                            $t->dropForeign($fk->getName());
                        }
                    }
                    $t->dropColumn('schoolyr_id');
                }

                if (Schema::hasColumn($table, 'deleted_at')) {
                    $t->dropSoftDeletes();
                }
            });
        }
    }
};
