<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Temporarily allow both legacy and new values
        DB::statement("
            ALTER TABLE `students`
            MODIFY `payment_status` ENUM('Paid','Not Paid','Unpaid','Partial')
            NULL DEFAULT 'Not Paid'
        ");

        // 2) Normalize data to the new vocabulary
        DB::table('students')->where('payment_status', 'Not Paid')->update(['payment_status' => 'Unpaid']);
        DB::table('students')->whereNull('payment_status')->update(['payment_status' => 'Unpaid']);
        DB::table('students')->whereNotIn('payment_status', ['Paid','Unpaid','Partial'])
            ->update(['payment_status' => 'Unpaid']);

        // 3) Final target enum (new schema)
        DB::statement("
            ALTER TABLE `students`
            MODIFY `payment_status` ENUM('Paid','Unpaid','Partial')
            NOT NULL DEFAULT 'Unpaid'
        ");

        // Optional helper column; avoid fragile AFTER
        if (!Schema::hasColumn('students', 's_total_due')) {
            Schema::table('students', function (Blueprint $table) {
                $table->decimal('s_total_due', 10, 2)->nullable(); // no AFTER to prevent errors
            });
        }
    }

};
