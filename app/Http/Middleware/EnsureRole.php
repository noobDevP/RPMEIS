<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    private const CLERK_ROLES = ['DENTAL', 'EENT', 'ECG', 'NP', 'LABORATORY', 'GPE'];

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! session('staff.logged_in')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $currentRole = strtoupper(trim((string) session('staff.role')));

        if ($roles !== [] && ! $this->matchesAllowedRoles($currentRole, $roles)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to perform this action.'], 403);
            }
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }

    /**
     * @param  array<int, string>  $allowedRoles
     */
    private function matchesAllowedRoles(string $currentRole, array $allowedRoles): bool
    {
        foreach ($allowedRoles as $role) {
            $role = strtoupper(trim($role));

            if ($currentRole === $role) {
                return true;
            }

            if ($role === 'DOCTOR_ANY' && ($currentRole === 'DOCTOR' || str_starts_with($currentRole, 'DOCTOR_'))) {
                return true;
            }

            if ($role === 'CLERK_ANY' && in_array($currentRole, self::CLERK_ROLES, true)) {
                return true;
            }
        }

        return false;
    }
}
