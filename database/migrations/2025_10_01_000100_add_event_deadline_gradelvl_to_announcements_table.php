<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (!Schema::hasColumn('announcements', 'date_of_event')) {
                $table->date('date_of_event')->nullable()->after('content');
            }
            if (!Schema::hasColumn('announcements', 'deadline')) {
                $table->date('deadline')->nullable()->after('date_of_event');
            }
            if (!Schema::hasColumn('announcements', 'gradelvl_id')) {
                $table->foreignId('gradelvl_id')->nullable()->after('deadline')
                      ->constrained('gradelvls')->nullOnDelete();
            }
        });
    }

};
