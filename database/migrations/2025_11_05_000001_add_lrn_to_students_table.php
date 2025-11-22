<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'lrn')) {
                // Create column and unique index in one go
                $table->string('lrn', 12)->after('id')->unique('students_lrn_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'lrn')) {
                // Drop unique index then column
                $table->dropUnique('students_lrn_unique');
                $table->dropColumn('lrn');
            }
        });
    }
};
