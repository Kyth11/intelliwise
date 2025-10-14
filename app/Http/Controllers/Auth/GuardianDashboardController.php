<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Models\Tuition;
use App\Models\Announcement;

class GuardianDashboardController extends Controller
{
    /**
     * Smart entry:
     * - If the logged-in user is a GUARDIAN → show guardian dashboard
     * - Otherwise (admin)                   → show admin guardians management page
     */
    public function index()
    {
        $user = Auth::user();

        // ----- GUARDIAN PANEL -----
        if ($user && $user->role === 'guardian') {
            // Eager-load everything the view needs (avoid N+1).
            $guardian = Guardian::with([
                'students.tuition',
                'students.optionalFees',
                'students.gradelvl',
                'students.payments',      // IMPORTANT for totals/last payment
            ])->find($user->guardian_id);

            if (!$guardian) {
                // Safety: no guardian record linked to the user yet
                return view('auth.guardiandashboard', [
                    'guardian'      => null,
                    'children'      => collect(),
                    'kpiLearners'   => 0,
                    'kpiBalance'    => 0.0,
                    'announcements' => collect(),
                ]);
            }

            // Students (already eager-loaded above)
            $children    = $guardian->students ?? collect();
            $kpiLearners = $children->count();

            // Build a quick lookup (only used as a fallback for legacy rows)
            $tuitionMap = Tuition::orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->get()
                ->keyBy('grade_level');

            // Compute per-student numbers and KPI balance
            $kpiBalance = 0.0;

            foreach ($children as $st) {
                // Base tuition (priority: relation → snapshot → by grade level via map)
                $base = 0.0;
                if ($st->relationLoaded('tuition') && $st->tuition) {
                    $base = (float) $st->tuition->total_yearly;
                } elseif (!empty($st->s_tuition_sum)) {
                    $base = (float) preg_replace('/[^\d.]+/', '', (string) $st->s_tuition_sum);
                } else {
                    $base = (float) optional($tuitionMap->get($st->s_gradelvl))->total_yearly;
                }

                // Student-level optional fees
                $opt = 0.0;
                if ($st->relationLoaded('optionalFees')) {
                    $opt = (float) $st->optionalFees->sum(function ($f) {
                        return (float) ($f->pivot->amount_override ?? $f->amount ?? 0);
                    });
                }

                $origTotal = $base + $opt;

                // Current balance prefers the s_total_due column, falls back to origTotal
                $currentBalance = isset($st->s_total_due) ? (float) $st->s_total_due : $origTotal;

                // Expose helpers for the Blade (if you still want to show base/opt somewhere)
                $st->_computed_base = $base;
                $st->_optional_sum  = $opt;
                $st->_computed_due  = $origTotal;

                // KPI = sum of current balances
                $kpiBalance += $currentBalance;
            }

            // ----- Announcements (same idea as before) -----
            $learnerGradeLevelIds = $children
                ->pluck('gradelvl.id')
                ->filter()
                ->unique()
                ->values();

            $announcements = Announcement::with(['gradelvls'])
                ->where(function ($q) use ($learnerGradeLevelIds) {
                    // Global announcements (no pivot rows) OR targeted to any of the learner's grades
                    $q->whereDoesntHave('gradelvls')
                      ->orWhereHas('gradelvls', function ($qq) use ($learnerGradeLevelIds) {
                          if ($learnerGradeLevelIds->isNotEmpty()) {
                              // Pivot uses the default key names: gradelvl_id
                              $qq->whereIn('gradelvl_id', $learnerGradeLevelIds);
                          } else {
                              // If no learners, still allow global ones; this branch becomes a no-op
                              $qq->whereRaw('1=0');
                          }
                      });
                })
                ->latest()
                ->get();

            return view('auth.guardiandashboard', [
                'guardian'      => $guardian,
                'children'      => $children,     // includes payments
                'kpiLearners'   => $kpiLearners,
                'kpiBalance'    => $kpiBalance,
                'announcements' => $announcements,
            ]);
        }

        // ----- ADMIN MANAGEMENT PAGE (unchanged) -----
        $guardians = Guardian::with(['students', 'user'])->get();
        $students  = Student::with('guardian')->get();

        return view('auth.admindashboard.guardians', compact('guardians', 'students'));
    }

    /**
     * Create a guardian (admin only).
     */
    public function store(Request $request)
    {
        $request->validate([
            'g_firstname' => 'required|string|max:255',
            'g_middlename'=> 'nullable|string|max:255',
            'g_lastname'  => 'required|string|max:255',
            'g_email'     => 'nullable|email|max:255|unique:guardians,g_email',
            'g_address'   => 'nullable|string|max:255',
            'g_contact'   => 'nullable|string|max:255',
            'username'    => 'required|string|max:255|unique:users,username',
            'password'    => 'required|string|min:6',
        ]);

        // Map single guardian name into mother columns (DB schema uses m_* / f_*)
        $guardian = Guardian::create([
            'g_address'    => $request->g_address,
            'g_contact'    => $request->g_contact,
            'g_email'      => $request->g_email,
            'm_firstname'  => $request->g_firstname,
            'm_middlename' => $request->g_middlename,
            'm_lastname'   => $request->g_lastname,
            'f_firstname'  => null,
            'f_middlename' => null,
            'f_lastname'   => null,
        ]);

        User::create([
            'name'        => trim($request->g_firstname.' '.$request->g_lastname),
            'username'    => $request->username,
            'password'    => bcrypt($request->password),
            'role'        => 'guardian',
            'guardian_id' => $guardian->id,
        ]);

        return back()->with('success', 'Guardian account created successfully!');
    }

    /**
     * Update guardian (admin or self).
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role === 'guardian' && (int)$user->guardian_id !== (int)$id) {
            abort(403, 'Unauthorized');
        }

        $guardian = Guardian::with('user')->findOrFail($id);

        $request->validate([
            'g_firstname' => 'nullable|string|max:255',
            'g_middlename'=> 'nullable|string|max:255',
            'g_lastname'  => 'nullable|string|max:255',
            'g_email'     => 'nullable|email|max:255|unique:guardians,g_email,' . $guardian->id,
            'g_address'   => 'nullable|string|max:255',
            'g_contact'   => 'nullable|string|max:255',
            'username'    => 'required|string|max:255|unique:users,username,' . optional($guardian->user)->id,
            'password'    => 'nullable|string|min:6',
        ]);

        $data = [
            'g_address' => $request->g_address,
            'g_contact' => $request->g_contact,
            'g_email'   => $request->g_email,
        ];
        if ($request->filled('g_firstname'))  $data['m_firstname']  = $request->g_firstname;
        if ($request->filled('g_middlename')) $data['m_middlename'] = $request->g_middlename;
        if ($request->filled('g_lastname'))   $data['m_lastname']   = $request->g_lastname;

        $guardian->update($data);
        $guardian->refresh();

        $displayFirst = $request->g_firstname
            ?? $guardian->m_firstname
            ?? $guardian->f_firstname
            ?? '';
        $displayLast  = $request->g_lastname
            ?? $guardian->m_lastname
            ?? $guardian->f_lastname
            ?? '';

        if ($guardian->user) {
            $guardian->user->update([
                'name'     => trim($displayFirst.' '.$displayLast),
                'username' => $request->username,
                'password' => $request->filled('password')
                    ? bcrypt($request->password)
                    : $guardian->user->password,
            ]);
        } else {
            if ($request->filled('username') && $request->filled('password')) {
                User::create([
                    'name'        => trim($displayFirst.' '.$displayLast),
                    'username'    => $request->username,
                    'password'    => bcrypt($request->password),
                    'role'        => 'guardian',
                    'guardian_id' => $guardian->id,
                ]);
            }
        }

        return back()->with('success', 'Guardian updated successfully!');
    }

    /**
     * Delete guardian (admin only).
     */
    public function destroy($id)
    {
        $guardian = Guardian::findOrFail($id);

        User::where('guardian_id', $guardian->id)->delete();
        $guardian->delete();

        return back()->with('success', 'Guardian account deleted successfully!');
    }
}
