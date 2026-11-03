<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\StaffUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'user_username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'rank' => ['nullable', 'string', 'max:255'],
            'afpos' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);

        StaffUser::create([
            'username' => strtoupper(trim($validated['username'])),
            'user_username' => trim($validated['user_username']),
            'password' => Hash::make($validated['password']),
            'active_stat' => '1',
            'rank' => trim($validated['rank'] ?? ''),
            'afpos' => trim($validated['afpos'] ?? ''),
            'status' => trim($validated['status'] ?? ''),
            'position' => trim($validated['position'] ?? ''),
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, int $userId)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'user_username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
            'rank' => ['nullable', 'string', 'max:255'],
            'afpos' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);

        $user = StaffUser::findOrFail($userId);
        $user->username = strtoupper(trim($validated['username']));
        $user->user_username = trim($validated['user_username']);
        $user->rank = trim($validated['rank'] ?? '');
        $user->afpos = trim($validated['afpos'] ?? '');
        $user->status = trim($validated['status'] ?? '');
        $user->position = trim($validated['position'] ?? '');

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.dashboard')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(int $userId)
    {
        $user = StaffUser::findOrFail($userId);
        $user->active_stat = '0';
        $user->save();

        return redirect()->route('admin.dashboard')
            ->with('success', 'User deleted successfully.');
    }
}
