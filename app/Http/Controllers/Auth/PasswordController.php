<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\StaffUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Legacy /change_password_api.php + /update_password.php + /change_password.php
     */
    public function changePassword(Request $request)
    {
        $userId = session('staff.user_id');
        $user = $userId ? StaffUser::find($userId) : null;

        if (! $user) {
            $sessionUsername = strtoupper(trim((string) session('staff.role')));
            if ($sessionUsername !== '') {
                $user = StaffUser::whereRaw('UPPER(TRIM(username)) = ?', [$sessionUsername])->first();
            }
        }

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $newPassword = trim((string) $request->input('new_password'));

        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                return response()->json(['status' => 'error', 'message' => 'Password must be at least 8 characters.'], 422);
            }

            $currentPassword = (string) $user->password;
            $isSame = Hash::isHashed($currentPassword) && Hash::check($newPassword, $currentPassword);

            if ($isSame) {
                return response()->json(['status' => 'error', 'message' => 'New password cannot be the same as current.'], 422);
            }

            $user->password = Hash::make($newPassword);
        }

        if ($request->has('user_username')) {
            $user->user_username = trim((string) $request->input('user_username'));
            session(['staff.display_name' => $user->user_username]);
        }

        if ($request->has('rank')) {
            $user->rank = trim((string) $request->input('rank')) ?: null;
        }
        if ($request->has('afpos')) {
            $user->afpos = trim((string) $request->input('afpos')) ?: null;
        }
        if ($request->has('status')) {
            $user->status = trim((string) $request->input('status')) ?: null;
        }
        if ($request->has('position')) {
            $user->position = trim((string) $request->input('position')) ?: null;
        }

        if ($request->hasFile('e_signature')) {
            $file = $request->file('e_signature');
            if ($file->isValid() && strtolower($file->getClientOriginalExtension()) === 'png') {
                $path = $file->store('signatures', 'public');
                $user->e_signature_path = $path;
            }
        }

        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Account settings updated successfully!']);
    }

    /**
     * Executive password change (/executive/change-password and /change_password_executive.php).
     */
    public function changePasswordExecutive(Request $request)
    {
        $userId = session('staff.user_id');
        $user = $userId ? StaffUser::find($userId) : null;

        if (! $user) {
            $sessionUsername = strtoupper(trim((string) session('staff.role')));
            if ($sessionUsername !== '') {
                $user = StaffUser::whereRaw('UPPER(TRIM(username)) = ?', [$sessionUsername])->first();
            }
        }

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $newPassword = trim((string) $request->input('newPassword'));
        if ($newPassword === '') {
            return response()->json(['status' => 'error', 'message' => 'New password is required.'], 422);
        }

        if (strlen($newPassword) < 8) {
            return response()->json(['status' => 'error', 'message' => 'Password must be at least 8 characters.'], 422);
        }

        $currentPassword = (string) $user->password;
        $isSame = Hash::isHashed($currentPassword) && Hash::check($newPassword, $currentPassword);

        if ($isSame) {
            return response()->json(['status' => 'error', 'message' => 'New password cannot be the same as current.'], 422);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Password updated successfully!']);
    }

    /**
     * Admin / chief password change (/update_password_chief.php).
     */
    public function changePasswordAdmin(Request $request)
    {
        $userId = session('staff.user_id');
        $user = $userId ? StaffUser::find($userId) : null;

        if (! $user) {
            $sessionUsername = strtoupper(trim((string) session('staff.role')));
            if ($sessionUsername !== '') {
                $user = StaffUser::whereRaw('UPPER(TRIM(username)) = ?', [$sessionUsername])->first();
            }
        }

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $newPassword = trim((string) $request->input('newPassword'));
        if ($newPassword === '') {
            return response()->json(['status' => 'error', 'message' => 'New password is required.'], 422);
        }

        if (strlen($newPassword) < 8) {
            return response()->json(['status' => 'error', 'message' => 'Password must be at least 8 characters.'], 422);
        }

        $currentPassword = (string) $user->password;
        $isSame = Hash::isHashed($currentPassword) && Hash::check($newPassword, $currentPassword);

        if ($isSame) {
            return response()->json(['status' => 'error', 'message' => 'New password cannot be the same as current.'], 422);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Password updated successfully!']);
    }
}
