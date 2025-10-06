<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 's_optional_total')) {
                $table->decimal('s_optional_total', 10, 2)->default(0)->after('s_tuition_sum');
            }
            if (!Schema::hasColumn('students', 's_total_due')) {
                $table->decimal('s_total_due', 10, 2)->default(0)->after('s_optional_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // safe no-op on down
            // $table->dropColumn(['s_optional_total', 's_total_due']);
        });
    }
};
