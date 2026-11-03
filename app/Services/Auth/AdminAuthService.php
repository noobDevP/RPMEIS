<?php

namespace App\Services\Auth;

use App\Models\AdminCredential;
use Illuminate\Support\Facades\Hash;

class AdminAuthService
{
    public function authenticate(string $usernameInput, string $passwordInput): ?AdminCredential
    {
        $username = strtoupper(trim($usernameInput));
        $password = trim($passwordInput);

        if ($username === '' || $password === '') {
            return null;
        }

        $admin = AdminCredential::query()
            ->whereRaw('UPPER(TRIM(admin_username)) = ?', [$username])
            ->first();

        if (! $admin) {
            return null;
        }

        $stored = (string) $admin->admin_password;

        if (Hash::isHashed($stored) && Hash::check($password, $stored)) {
            if (Hash::needsRehash($stored)) {
                $admin->update(['admin_password' => Hash::make($password)]);
            }

            return $admin;
        }

        if (hash_equals($stored, $password)) {
            $admin->update(['admin_password' => Hash::make($password)]);

            return $admin;
        }

        return null;
    }
}
