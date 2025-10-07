<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Models\Tuition;

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
            // Eager load tuition + optional fees to avoid N+1 and let us compute totals
            $guardian = Guardian::with([
                'students.tuition',
                'students.optionalFees',
                'students.gradelvl',
            ])->find($user->guardian_id);

            // Build a quick lookup: grade_level -> latest tuition total_yearly (fallback for legacy rows)
            $tuitionMap = Tuition::orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->get()
                ->keyBy('grade_level');

            $children    = $guardian?->students ?? collect();
            $kpiLearners = $children->count();
            $kpiBalance  = 0.0;

            // Compute per-student due: base tuition + student optional fees (no payments yet)
            foreach ($children as $st) {
                // base tuition
                $base = 0.0;
                if ($st->relationLoaded('tuition') && $st->tuition) {
                    $base = (float) $st->tuition->total_yearly;
                } elseif (!empty($st->s_tuition_sum)) {
                    // legacy snapshot string
                    $base = (float) preg_replace('/[^\d.]+/', '', (string) $st->s_tuition_sum);
                } else {
                    // final fallback by s_gradelvl
                    $base = (float) optional($tuitionMap->get($st->s_gradelvl))->total_yearly;
                }

                // student-level optional fees (use pivot override when present)
                $opt = 0.0;
                if ($st->relationLoaded('optionalFees')) {
                    $opt = (float) $st->optionalFees->sum(function ($f) {
                        return (float) ($f->pivot->amount_override ?? $f->amount ?? 0);
                    });
                }

                // expose to the view and aggregate the KPI
                $st->_computed_due  = $base + $opt;
                $st->_computed_base = $base;
                $st->_optional_sum  = $opt;

                $kpiBalance += $st->_computed_due;
            }

            // ----- Announcements (kept same as before) -----
            $announcementQuery = \App\Models\Announcement::with(['gradelvls'])->latest();
            $learnerGradeLevelIds = $children
                ->pluck('gradelvl.id')
                ->filter()
                ->unique()
                ->values();

            $announcements = $announcementQuery->where(function ($q) use ($learnerGradeLevelIds) {
                $q->whereDoesntHave('gradelvls')
                  ->orWhereHas('gradelvls', function ($qq) use ($learnerGradeLevelIds) {
                      if ($learnerGradeLevelIds->isNotEmpty()) {
                          $qq->whereIn('grade_level_id', $learnerGradeLevelIds);
                      } else {
                          // If no learners, still allow global announcements
                          $qq->whereRaw('1=0');
                      }
                  });
            })->get();

            return view('auth.guardiandashboard', [
                'guardian'      => $guardian,
                'children'      => $children,
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

        // Map single guardian name into existing mother columns (DB has m_* / f_*)
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
