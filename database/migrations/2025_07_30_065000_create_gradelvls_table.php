<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gradelvls', function (Blueprint $table) {
            $table->id();
            $table->string('grade_level'); // "Grade 1", "Grade 2", ...
            $table->timestamps();
        });

        DB::table('gradelvls')->insert([
            ['grade_level' => 'Nursery'],
            ['grade_level' => 'Kindergarten 1'],
            ['grade_level' => 'Kindergarten 2'],
            ['grade_level' => 'Grade 1'],
            ['grade_level' => 'Grade 2'],
            ['grade_level' => 'Grade 3'],
            ['grade_level' => 'Grade 4'],
            ['grade_level' => 'Grade 5'],
            ['grade_level' => 'Grade 6'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('gradelvls');
    }
};
