<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log(string $action, ?string $patientNo = null, ?string $username = null, ?string $role = null, ?string $ip = null): void
    {
        $request = request();
        
        if ($username === null) {
            if (session()->has('staff.display_name')) {
                $username = session('staff.display_name');
            } elseif (session()->has('admin.username')) {
                $username = session('admin.username');
            } elseif (session()->has('user.username')) {
                $username = session('user.username');
            } else {
                $username = 'System';
            }
        }
        
        if ($role === null) {
            if (session()->has('staff.role')) {
                $role = session('staff.role');
            } elseif (session()->has('admin.logged_in')) {
                $role = 'ADMIN';
            } elseif (session()->has('user.role')) {
                $role = session('user.role');
            } else {
                $role = 'GUEST';
            }
        }
        
        if ($ip === null) {
            $ip = $request ? $request->ip() : null;
        }

        ActivityLog::create([
            'user_username' => $username,
            'role' => $role,
            'action' => $action,
            'patient_no' => $patientNo,
            'ip_address' => $ip,
        ]);
    }
}