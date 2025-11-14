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
            'enrollment', // already has schoolyr_id in your schema; we'll only ensure softDeletes if needed
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                // Add schoolyr_id ONLY if missing
                if (! Schema::hasColumn($table, 'schoolyr_id')) {
                    // Choose a safe anchor: prefer 'id', else 'lrn', else no after()
                    $anchor = null;
                    if (Schema::hasColumn($table, 'id')) {
                        $anchor = 'id';
                    } elseif (Schema::hasColumn($table, 'lrn')) {
                        $anchor = 'lrn';
                    }

                    if ($anchor) {
                        $t->unsignedBigInteger('schoolyr_id')->nullable()->after($anchor);
                    } else {
                        $t->unsignedBigInteger('schoolyr_id')->nullable();
                    }

                    // Helpful index + FK
                    $t->index('schoolyr_id', "{$table}_schoolyr_id_idx");
                    $t->foreign('schoolyr_id', "{$table}_schoolyr_id_fk")
                        ->references('id')->on('schoolyrs')
                        ->nullOnDelete()->cascadeOnUpdate();
                }

                // Add deleted_at (soft deletes) if missing
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
            'enrollment', // we won't force-remove schoolyr_id here if you didn't add it in this migration
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                // Only drop schoolyr_id if present AND if this migration likely added it.
                // If your 'enrollment' table already had it historically, keeping it is safer.
                if ($table !== 'enrollment' && Schema::hasColumn($table, 'schoolyr_id')) {
                    // Drop FK & index then column (try/catch to be idempotent)
                    try { $t->dropForeign("{$table}_schoolyr_id_fk"); } catch (\Throwable $e) {}
                    try { $t->dropIndex("{$table}_schoolyr_id_idx"); } catch (\Throwable $e) {}
                    $t->dropColumn('schoolyr_id');
                }

                if (Schema::hasColumn($table, 'deleted_at')) {
                    $t->dropSoftDeletes();
                }
            });
        }
    }
};
