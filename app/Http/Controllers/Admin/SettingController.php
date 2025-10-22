<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\Schoolyr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    /**
     * PUT /admin/settings/system
     */
    public function updateSystem(Request $request)
    {
        $enabled = $request->has('faculty_enrollment_enabled')
            ? $request->boolean('faculty_enrollment_enabled')
            : false;

        AppSetting::set('faculty_enrollment_enabled', $enabled);

        return back()->with('success', 'System settings saved.');
    }

    /**
     * POST /admin/settings/admins
     */
    public function storeAdmin(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['in:admin'],
        ]);

        User::create([
            'name'        => $data['name'],
            'username'    => $data['username'],
            'password'    => Hash::make($data['password']),
            'role'        => 'admin',
            'faculty_id'  => null,
            'guardian_id' => null,
        ]);

        return back()->with('success', 'Admin account created.');
    }

    /**
     * DELETE /admin/settings/admins/{id}
     */
    public function destroyAdmin($id)
    {
        $toDelete = User::where('id', $id)->where('role', 'admin')->firstOrFail();

        if (Auth::id() === $toDelete->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $adminCount = User::where('role', 'admin')->count();
        if ($adminCount <= 1) {
            return back()->with('error', 'Cannot delete the last admin account.');
        }

        $toDelete->delete();
        return back()->with('success', 'Admin account deleted.');
    }

    /**
     * POST /admin/settings/school-year
     */
    public function storeSchoolYear(Request $request)
    {
        $data = $request->validate([
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/', 'unique:schoolyrs,school_year'],
        ]);

        Schoolyr::create(['school_year' => $data['school_year']]);

        return back()->with('success', 'School year added.');
    }

    /**
     * POST /admin/settings/gcash-qr
     * Upload/replace GCash QR image and save relative public path in AppSetting('gcash_qr_path').
     * Stores to storage/app/public/gcash/... and serves via /storage/gcash/...
     */
    public function uploadGcashQr(Request $request)
    {
        $request->validate([
            // keep QR as an image (PDFs won’t render in <img>)
            'gcash_qr' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'], // 5MB
        ]);

        // Delete the old file if present (normalize legacy values too)
        $old = AppSetting::get('gcash_qr_path'); // e.g. "gcash/old.png"
        if ($old) {
            $oldNormalized = Str::startsWith($old, 'public/') ? Str::after($old, 'public/') : $old;
            if (Storage::disk('public')->exists($oldNormalized)) {
                try { Storage::disk('public')->delete($oldNormalized); } catch (\Throwable $e) {}
            } elseif (Storage::exists($old)) {
                try { Storage::delete($old); } catch (\Throwable $e) {}
            }
        }

        // Save to PUBLIC disk; returns e.g. "gcash/XYZ123.png"
        $path = $request->file('gcash_qr')->store('gcash', 'public');

        // Persist only the relative path
        AppSetting::set('gcash_qr_path', $path);

        return back()->with('success', 'GCash QR updated.');
    }
}
