<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'lrn')) {
                $table->string('lrn', 12)->after('id');
            }
            // Make sure it's indexed/unique for fast lookups & FK refs
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            // Just add a unique index if it doesn't exist yet:
            $table->unique('lrn', 'students_lrn_unique');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'lrn')) {
                $table->dropUnique('students_lrn_unique');
                $table->dropColumn('lrn');
            }
        });
    }
};
