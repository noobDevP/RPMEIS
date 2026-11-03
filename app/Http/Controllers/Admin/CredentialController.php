<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCredentialRequest;
use App\Models\AdminCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CredentialController extends Controller
{
    public function update(UpdateCredentialRequest $request)
    {
        $validated = $request->validate([
            'admin_username' => ['required', 'string', 'max:255'],
            'admin_password' => ['required', 'string', 'min:6'],
        ]);

        $credential = AdminCredential::findOrFail(1);

        $credential->admin_username = strtoupper(trim($validated['admin_username']));
        $credential->admin_password = Hash::make($validated['admin_password']);
        $credential->save();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Credentials updated successfully.');
    }

    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $credential = AdminCredential::find(session('admin.id', 1));
        $valid = false;
        if ($credential) {
            $stored = (string) $credential->admin_password;
            if (Hash::isHashed($stored) && Hash::check($request->password, $stored)) {
                $valid = true;
                if (Hash::needsRehash($stored)) {
                    $credential->admin_password = Hash::make($request->password);
                    $credential->save();
                }
            } elseif (hash_equals($stored, $request->password)) {
                $valid = true;
                $credential->admin_password = Hash::make($request->password);
                $credential->save();
            }
        }

        return response()->json(['valid' => $valid]);
    }
}
