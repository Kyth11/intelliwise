<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // STEP 1: Temporarily allow both old and new values so updates won't fail
        // Keep it nullable here to avoid issues if you have NULLs
        DB::statement("
            ALTER TABLE `students`
            MODIFY `payment_status` ENUM('Paid','Not Paid','Unpaid','Partial')
            NULL DEFAULT 'Not Paid'
        ");

        // STEP 2: Normalize data safely now that 'Unpaid' is allowed
        DB::table('students')->where('payment_status', 'Not Paid')->update(['payment_status' => 'Unpaid']);
        DB::table('students')->whereNull('payment_status')->update(['payment_status' => 'Unpaid']);
        // If any other odd strings slipped in previously, coerce them:
        DB::table('students')->whereNotIn('payment_status', ['Paid','Unpaid','Partial'])
            ->update(['payment_status' => 'Unpaid']);

        // STEP 3: Shrink enum to the final allowed set and make it NOT NULL
        DB::statement("
            ALTER TABLE `students`
            MODIFY `payment_status` ENUM('Paid','Unpaid','Partial')
            NOT NULL DEFAULT 'Unpaid'
        ");

        // Optional: ensure s_total_due column exists (if your schema might lack it)
        if (!Schema::hasColumn('students', 's_total_due')) {
            Schema::table('students', function (Blueprint $table) {
                $table->decimal('s_total_due', 10, 2)->nullable()->after('s_optional_total');
            });
        }
    }

    public function down(): void
    {
        // Go back to legacy values
        DB::statement("
            ALTER TABLE `students`
            MODIFY `payment_status` ENUM('Paid','Not Paid')
            NULL DEFAULT 'Not Paid'
        ");

        // Convert 'Unpaid' back to 'Not Paid' for legacy compatibility
        DB::table('students')->where('payment_status', 'Unpaid')->update(['payment_status' => 'Not Paid']);
        // If you really want to drop 'Partial' data in down(), coerce it too:
        DB::table('students')->where('payment_status', 'Partial')->update(['payment_status' => 'Not Paid']);
    }
};
