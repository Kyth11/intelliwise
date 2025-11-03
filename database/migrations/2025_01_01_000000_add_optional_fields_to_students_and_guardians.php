<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students','s_gender'))          $table->string('s_gender')->nullable()->after('s_lastname');
            if (!Schema::hasColumn('students','previous_school'))   $table->string('previous_school')->nullable()->after('s_gradelvl');
            if (!Schema::hasColumn('students','sped_has'))          $table->string('sped_has')->nullable()->after('s_email');
            if (!Schema::hasColumn('students','sped_desc'))         $table->text('sped_desc')->nullable()->after('sped_has');
        });

        Schema::table('guardians', function (Blueprint $table) {
            if (!Schema::hasColumn('guardians','g_email'))               $table->string('g_email')->nullable()->after('g_contact');
            if (!Schema::hasColumn('guardians','m_occupation'))          $table->string('m_occupation')->nullable()->after('m_email');
            if (!Schema::hasColumn('guardians','f_occupation'))          $table->string('f_occupation')->nullable()->after('f_email');
            if (!Schema::hasColumn('guardians','alt_guardian_details'))  $table->string('alt_guardian_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            foreach (['s_gender','previous_school','sped_has','sped_desc'] as $col) {
                if (Schema::hasColumn('students', $col)) $table->dropColumn($col);
            }
        });
        Schema::table('guardians', function (Blueprint $table) {
            foreach (['g_email','m_occupation','f_occupation','alt_guardian_details'] as $col) {
                if (Schema::hasColumn('guardians', $col)) $table->dropColumn($col);
            }
        });
    }
};
