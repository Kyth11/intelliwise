<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add the 'active' column if it does not exist yet
        Schema::table('schoolyrs', function (Blueprint $table) {
            if (! Schema::hasColumn('schoolyrs', 'active')) {
                $table->boolean('active')
                    ->default(false)
                    ->after('school_year'); // place after school_year for readability
            }
        });

        // Mark 2025-2026 as the active school year if it exists
        DB::table('schoolyrs')
            ->where('school_year', '2025-2026')
            ->update(['active' => true]);
    }

    public function down(): void
    {
        // Drop the 'active' column if it exists (safe rollback)
        Schema::table('schoolyrs', function (Blueprint $table) {
            if (Schema::hasColumn('schoolyrs', 'active')) {
                $table->dropColumn('active');
            }
        });
    }
};
