<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop FK if it was created with the default name
        try {
            Schema::table('tuitions', function (Blueprint $table) {
                // If a foreign key exists on school_year, drop it
                // Default name would be tuitions_school_year_foreign
                $table->dropForeign(['school_year']);
            });
        } catch (\Throwable $e) {
            // no-op: FK didn't exist
        }

        Schema::table('tuitions', function (Blueprint $table) {
            // If column doesn't exist, add it. If it exists but wrong type, change it.
            if (!Schema::hasColumn('tuitions', 'school_year')) {
                $table->string('school_year', 9)->nullable()->after('total_yearly');
            } else {
                // Requires doctrine/dbal (you already installed it)
                $table->string('school_year', 9)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Optional: revert to previous state (keep the column but make it nullable string anyway)
        Schema::table('tuitions', function (Blueprint $table) {
            // no destructive revert to avoid data loss
        });
    }
};
