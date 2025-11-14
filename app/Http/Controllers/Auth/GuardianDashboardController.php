<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Announcement;

class GuardianDashboardController extends Controller
{
    /**
     * Guardian dashboard (view-only). Admin guardians management moved to Admin\GuardianController.
     */
    public function index()
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'guardian', 403);

        $guardian = Guardian::with([
            'students.tuition',
            'students.optionalFees',
            'students.gradelvl',
            'students.payments',
        ])->find($user->guardian_id);

        if (!$guardian) {
            return view('auth.guardiandashboard', [
                'guardian'      => null,
                'children'      => collect(),
                'kpiLearners'   => 0,
                'kpiBalance'    => 0.0,
                'announcements' => collect(),
            ]);
        }

        $children    = $guardian->students ?? collect();
        $kpiLearners = $children->count();

        $tuitionMap = \App\Models\Tuition::orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get()
            ->keyBy('grade_level');

        $kpiBalance = 0.0;

        foreach ($children as $st) {
            $base = 0.0;
            if ($st->relationLoaded('tuition') && $st->tuition) {
                $base = (float) $st->tuition->total_yearly;
            } elseif (!empty($st->s_tuition_sum)) {
                $base = (float) preg_replace('/[^\d.]+/', '', (string) $st->s_tuition_sum);
            } else {
                $base = (float) optional($tuitionMap->get($st->s_gradelvl))->total_yearly;
            }

            $opt = 0.0;
            if ($st->relationLoaded('optionalFees')) {
                $opt = (float) $st->optionalFees->sum(function ($f) {
                    return (float) ($f->pivot->amount_override ?? $f->amount ?? 0);
                });
            }

            $origTotal      = $base + $opt;
            $currentBalance = isset($st->s_total_due) ? (float) $st->s_total_due : $origTotal;
            $st->_computed_base = $base;
            $st->_optional_sum  = $opt;
            $st->_computed_due  = $origTotal;
            $kpiBalance += $currentBalance;
        }

        $learnerGradeLevelIds = $children
            ->pluck('gradelvl.id')
            ->filter()
            ->unique()
            ->values();

        $announcements = Announcement::with(['gradelvls'])
            ->where(function ($q) use ($learnerGradeLevelIds) {
                $q->whereDoesntHave('gradelvls')
                  ->orWhereHas('gradelvls', function ($qq) use ($learnerGradeLevelIds) {
                      if ($learnerGradeLevelIds->isNotEmpty()) {
                          $qq->whereIn('gradelvl_id', $learnerGradeLevelIds);
                      } else {
                          $qq->whereRaw('1=0');
                      }
                  });
            })
            ->latest()
            ->get();

        return view('auth.guardiandashboard', [
            'guardian'      => $guardian,
            'children'      => $children,
            'kpiLearners'   => $kpiLearners,
            'kpiBalance'    => $kpiBalance,
            'announcements' => $announcements,
        ]);
    }
}
