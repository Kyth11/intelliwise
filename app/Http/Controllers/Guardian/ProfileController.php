<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function upsert(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'guardian', 403);

        if (!empty($user->guardian_id)) {
            return $this->update($request, $user->guardian_id);
        }

        $request->validate([
            'g_firstname'  => 'nullable|string|max:255',
            'g_middlename' => 'nullable|string|max:255',
            'g_lastname'   => 'nullable|string|max:255',
            'g_email'   => 'nullable|email|max:255|unique:guardians,g_email',
            'g_address' => 'nullable|string|max:255',
            'g_contact' => 'nullable|string|max:255',
            'username' => ['required','string','max:255', Rule::unique('users','username')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($request, $user) {
            $guardian = Guardian::create([
                'g_address'    => $request->input('g_address'),
                'g_contact'    => $request->input('g_contact'),
                'g_email'      => $request->input('g_email'),
                'm_firstname'  => $request->input('g_firstname'),
                'm_middlename' => $request->input('g_middlename'),
                'm_lastname'   => $request->input('g_lastname'),
                'f_firstname'  => null,
                'f_middlename' => null,
                'f_lastname'   => null,
            ]);

            $displayFirst = $guardian->m_firstname ?: $guardian->f_firstname ?: '';
            $displayLast  = $guardian->m_lastname  ?: $guardian->f_lastname  ?: '';

            $user->guardian_id = $guardian->id;
            $user->name        = trim($displayFirst.' '.$displayLast) ?: ($user->name ?? '');
            $user->username    = $request->input('username');
            if ($request->filled('password')) {
                $user->password = bcrypt($request->input('password'));
            }
           try {
    $user->save();
} catch (\Exception $e) {
    // Log or display the error message
    logger()->error($e->getMessage());
    // Optionally, you can re-throw the exception if needed
    throw $e;
}
        });

        return back()->with('success', 'Profile created and linked.');
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role === 'guardian' && (int)$user->guardian_id !== (int)$id) {
            abort(403, 'Unauthorized');
        }

        $guardian = Guardian::with('user')->findOrFail($id);
        $currentUserId = optional($guardian->user)->id;

        $request->validate([
            'g_firstname' => 'nullable|string|max:255',
            'g_middlename'=> 'nullable|string|max:255',
            'g_lastname'  => 'nullable|string|max:255',
            'g_email'     => 'nullable|email|max:255|unique:guardians,g_email,' . $guardian->id,
            'g_address'   => 'nullable|string|max:255',
            'g_contact'   => 'nullable|string|max:255',
            'username'    => ['required','string','max:255', Rule::unique('users','username')->ignore($currentUserId)],
            'password'    => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($request, $guardian) {
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

            $displayFirst = $guardian->m_firstname ?: $guardian->f_firstname ?: '';
            $displayLast  = $guardian->m_lastname  ?: $guardian->f_lastname  ?: '';

            if ($guardian->user) {
                $payload = [
                    'name'     => trim($displayFirst.' '.$displayLast),
                    'username' => $request->username,
                ];
                if ($request->filled('password')) {
                    $payload['password'] = bcrypt($request->password);
                }
                $guardian->user->update($payload);
            } else {
                User::create([
                    'name'        => trim($displayFirst.' '.$displayLast),
                    'username'    => $request->username,
                    'password'    => bcrypt($request->filled('password') ? $request->password : 'password123'),
                    'role'        => 'guardian',
                    'guardian_id' => $guardian->id,
                ]);
            }
        });

        return back()->with('success', 'Guardian updated successfully!');
    }
}
