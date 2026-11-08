<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\StaffUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function logs(Request $request): View|RedirectResponse
    {
        if (! session('admin.logged_in')) {
            return redirect()->route('admin.login');
        }

        $query = \App\Models\ActivityLog::query();

        $filter = $request->query('filter');

        if ($filter === 'logins') {
            $query->whereIn('action', ['Logged In', 'Logged Out']);
        } elseif ($filter === 'medical') {
            $query->whereIn('action', ['Marked Applicant Cleared', 'Marked Applicant GO', 'Marked Applicant Disqualified', 'Marked Applicant as Done', 'Returned Applicant']);
        } elseif ($filter === 'downloads') {
            $query->whereIn('action', ['Downloaded Redform', 'Downloaded Medical Certificate']);
        }

        $activityLogs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('admin.logs', compact('activityLogs', 'filter'));
    }
}
