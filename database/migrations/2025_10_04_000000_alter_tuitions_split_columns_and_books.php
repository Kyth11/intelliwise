<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add new columns if missing
        Schema::table('tuitions', function (Blueprint $table) {
            if (!Schema::hasColumn('tuitions', 'tuition_monthly')) {
                $table->decimal('tuition_monthly', 10, 2)->default(0)->after('grade_level');
            }
            if (!Schema::hasColumn('tuitions', 'tuition_yearly')) {
                $table->decimal('tuition_yearly', 10, 2)->default(0)->after('tuition_monthly');
            }

            if (!Schema::hasColumn('tuitions', 'misc_monthly')) {
                $table->decimal('misc_monthly', 10, 2)->nullable()->after('tuition_yearly');
            }
            if (!Schema::hasColumn('tuitions', 'misc_yearly')) {
                $table->decimal('misc_yearly', 10, 2)->nullable()->after('misc_monthly');
            }

            if (!Schema::hasColumn('tuitions', 'books_desc')) {
                $table->string('books_desc')->nullable()->after('misc_yearly');
            }
            if (!Schema::hasColumn('tuitions', 'books_amount')) {
                $table->decimal('books_amount', 10, 2)->nullable()->after('books_desc');
            }

            if (!Schema::hasColumn('tuitions', 'total_yearly')) {
                $table->decimal('total_yearly', 10, 2)->default(0)->after('books_amount');
            }
            if (!Schema::hasColumn('tuitions', 'school_year')) {
                $table->string('school_year', 9)->nullable()->after('total_yearly');
            }
        });

        // 2) Backfill from old columns if they exist
        $hasMonthly = Schema::hasColumn('tuitions', 'monthly_fee');
        $hasYearly  = Schema::hasColumn('tuitions', 'yearly_fee');
        $hasMisc    = Schema::hasColumn('tuitions', 'misc_fee');
        $hasOptDesc = Schema::hasColumn('tuitions', 'optional_fee_desc');
        $hasOptAmt  = Schema::hasColumn('tuitions', 'optional_fee_amount');

        // If nothing to backfill, stop here
        if (!$hasMonthly && !$hasYearly && !$hasMisc && !$hasOptAmt) {
            return;
        }

        // Use chunks for safety on large tables
        DB::table('tuitions')
            ->select('id',
                $hasMonthly ? 'monthly_fee' : DB::raw('NULL as monthly_fee'),
                $hasYearly  ? 'yearly_fee'  : DB::raw('NULL as yearly_fee'),
                $hasMisc    ? 'misc_fee'    : DB::raw('NULL as misc_fee'),
                $hasOptDesc ? 'optional_fee_desc'   : DB::raw('NULL as optional_fee_desc'),
                $hasOptAmt  ? 'optional_fee_amount' : DB::raw('NULL as optional_fee_amount')
            )
            ->orderBy('id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $r) {
                    $months = 10;

                    // Tuition
                    $tMon  = !is_null($r->monthly_fee) ? (float)$r->monthly_fee : null;
                    $tYear = !is_null($r->yearly_fee)  ? (float)$r->yearly_fee  : null;

                    if (is_null($tMon) && is_null($tYear)) {
                        $tMon = 0.0; $tYear = 0.0;
                    } elseif (is_null($tMon)) {
                        $tMon = $tYear / $months;
                    } elseif (is_null($tYear)) {
                        $tYear = $tMon * $months;
                    }

                    // Misc (old system had a single misc_fee for whole year)
                    $miscYear = !is_null($r->misc_fee) ? (float)$r->misc_fee : null;
                    $miscMon  = !is_null($miscYear) ? ($miscYear / $months) : null;

                    // Optional (old per-row optional applied to grade-level total, we map to books for preservation)
                    $booksAmt = !is_null($r->optional_fee_amount) ? (float)$r->optional_fee_amount : null;
                    $booksDesc = $r->optional_fee_desc ?? null;

                    $baseTotal = round($tYear + ($miscYear ?: 0) + ($booksAmt ?: 0), 2);

                    DB::table('tuitions')->where('id', $r->id)->update([
                        'tuition_monthly' => round($tMon, 2),
                        'tuition_yearly'  => round($tYear, 2),
                        'misc_monthly'    => is_null($miscMon)  ? null : round($miscMon, 2),
                        'misc_yearly'     => is_null($miscYear) ? null : round($miscYear, 2),
                        'books_desc'      => $booksDesc,
                        'books_amount'    => is_null($booksAmt) ? null : round($booksAmt, 2),
                        'total_yearly'    => $baseTotal,
                    ]);
                }
            });

        // 3) (Optional) Drop old columns — comment out if you don’t have doctrine/dbal installed
        // if (Schema::hasColumn('tuitions', 'monthly_fee') ||
        //     Schema::hasColumn('tuitions', 'yearly_fee')  ||
        //     Schema::hasColumn('tuitions', 'misc_fee')    ||
        //     Schema::hasColumn('tuitions', 'optional_fee_desc') ||
        //     Schema::hasColumn('tuitions', 'optional_fee_amount')) {
        //     Schema::table('tuitions', function (Blueprint $table) {
        //         if (Schema::hasColumn('tuitions', 'monthly_fee')) $table->dropColumn('monthly_fee');
        //         if (Schema::hasColumn('tuitions', 'yearly_fee'))  $table->dropColumn('yearly_fee');
        //         if (Schema::hasColumn('tuitions', 'misc_fee'))    $table->dropColumn('misc_fee');
        //         if (Schema::hasColumn('tuitions', 'optional_fee_desc'))   $table->dropColumn('optional_fee_desc');
        //         if (Schema::hasColumn('tuitions', 'optional_fee_amount')) $table->dropColumn('optional_fee_amount');
        //     });
        // }
    }

    public function down(): void
    {
        // no-op: tables will be dropped by base 'create' migrations during refresh
    }
};
