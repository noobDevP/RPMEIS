<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\StaffUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! session('admin.logged_in')) {
            return redirect()->route('admin.login');
        }

        $users = StaffUser::query()
            ->where('active_stat', '1')
            ->orderBy('user_id')
            ->get();

        $activityLogs = \App\Models\ActivityLog::orderByDesc('created_at')->limit(100)->get();

        $places = Place::query()
            ->orderBy('place_name')
            ->get();

        $executiveLogs = \App\Models\ExecutiveLog::query()
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
            
        $signingDoctors = \App\Models\SigningDoctor::orderBy('name')->get();
        $approvingAuthorities = \App\Models\ApprovingAuthority::getAllWithChiefDoctor();

        $activityLogs = \App\Models\ActivityLog::orderByDesc('created_at')->limit(100)->get();
        return view('admin.dashboard', compact('users', 'places', 'executiveLogs', 'signingDoctors', 'approvingAuthorities', 'activityLogs'));
    }
}
