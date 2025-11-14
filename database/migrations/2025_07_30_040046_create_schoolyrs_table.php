<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schoolyrs', function (Blueprint $table) {
            $table->id(); // ← primary key (auto-increment)
            $table->string('school_year', 9)->unique(); // e.g. '2025-2026'
            $table->tinyInteger('active'); // e.g. '2025-2026'
            $table->timestamps();
        });

        DB::table('schoolyrs')->insert([
            ['school_year' => '2025-2026' , 'active' => 1],
            ['school_year' => '2026-2027' , 'active' => 0],
            ['school_year' => '2027-2028' , 'active' => 0],
            ['school_year' => '2028-2029' , 'active' => 0],
            ['school_year' => '2029-2030' , 'active' => 0],

        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('schoolyrs');
    }
};
