<?php

namespace App\Services\Auth;

use App\Models\StaffUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class StaffAuthService
{
    public function authenticate(string $usernameInput, string $passwordInput): ?StaffUser
    {
        $username = strtoupper(trim($usernameInput));
        $password = trim($passwordInput);

        if ($username === '' || $password === '') {
            return null;
        }

        try {
            $candidates = StaffUser::query()
                ->whereRaw('UPPER(TRIM(username)) = ?', [$username])
                ->get();
        } catch (Throwable $e) {
            Log::warning('Staff authentication database query failed.', [
                'username' => $username,
                'exception' => $e,
            ]);

            return null;
        }

        foreach ($candidates as $user) {
            if (! $user->isActive()) {
                continue;
            }

            if ($this->passwordMatches($user, $password)) {
                return $user;
            }
        }

        return null;
    }

    public function lookupDisplayName(string $usernameInput, string $passwordInput): ?string
    {
        $user = $this->authenticate($usernameInput, $passwordInput);

        return $user?->user_username;
    }

    public function redirectPathFor(StaffUser $user): string
    {
        $role = strtoupper(trim((string) $user->username));

        return config("roles.legacy_redirects.{$role}") ?? '/login';
    }

    private function passwordMatches(StaffUser $user, string $password): bool
    {
        $stored = (string) $user->password;

        if ($stored === '' || ! Hash::isHashed($stored)) {
            return false;
        }

        if (Hash::check($password, $stored)) {
            if (Hash::needsRehash($stored)) {
                $user->update(['password' => Hash::make($password)]);
            }

            return true;
        }

        return false;
    }
}
