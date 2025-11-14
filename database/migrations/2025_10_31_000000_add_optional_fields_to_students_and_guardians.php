<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Only alter if base tables already exist (prevents "table doesn't exist" on fresh runs)
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (!Schema::hasColumn('students','s_gender'))         $table->string('s_gender')->nullable()->after('s_lastname');
                if (!Schema::hasColumn('students','previous_school'))  $table->string('previous_school')->nullable()->after('s_gradelvl');
                if (!Schema::hasColumn('students','sped_has'))         $table->string('sped_has')->nullable()->after('s_email');
                if (!Schema::hasColumn('students','sped_desc'))        $table->text('sped_desc')->nullable()->after('sped_has');
            });
        }

        if (Schema::hasTable('guardians')) {
            Schema::table('guardians', function (Blueprint $table) {
                if (!Schema::hasColumn('guardians','g_email'))              $table->string('g_email')->nullable()->after('g_contact');
                if (!Schema::hasColumn('guardians','m_occupation'))         $table->string('m_occupation')->nullable()->after('m_email');
                if (!Schema::hasColumn('guardians','f_occupation'))         $table->string('f_occupation')->nullable()->after('f_email');
                if (!Schema::hasColumn('guardians','alt_guardian_details')) $table->string('alt_guardian_details')->nullable();
            });
        }
    }

};
