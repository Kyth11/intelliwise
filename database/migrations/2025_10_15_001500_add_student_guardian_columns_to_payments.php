<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'guardian_id')) {
                $table->unsignedBigInteger('guardian_id')->nullable()->after('tuition_id');
                $table->foreign('guardian_id')
                    ->references('id')->on('guardians')
                    ->onDelete('set null')->onUpdate('cascade');
            }

            if (!Schema::hasColumn('payments', 'student_id')) {
                $table->char('student_id', 12)->nullable()->after('guardian_id');
                $table->foreign('student_id')
                    ->references('lrn')->on('students')
                    ->onDelete('set null')->onUpdate('cascade');
            }

            if (!Schema::hasColumn('payments', 'guardian_name')) {
                $table->string('guardian_name')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('payments', 'mother_name')) {
                $table->string('mother_name')->nullable()->after('guardian_name');
            }
            if (!Schema::hasColumn('payments', 'father_name')) {
                $table->string('father_name')->nullable()->after('mother_name');
            }
            if (!Schema::hasColumn('payments', 'payer_name')) {
                $table->string('payer_name')->nullable()->after('father_name');
            }
        });
    }

};
