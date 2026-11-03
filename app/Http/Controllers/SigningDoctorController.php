<?php

namespace App\Http\Controllers;

use App\Models\SigningDoctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SigningDoctorController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rank' => 'required|string|max:255',
            'afpos' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'position' => 'required|string|max:255',
        ]);

        SigningDoctor::create($request->only('name', 'rank', 'afpos', 'status', 'position'));

        return Redirect::back()->with('success', 'Signing doctor created successfully.');
    }

    public function update(Request $request, SigningDoctor $signingDoctor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rank' => 'required|string|max:255',
            'afpos' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'position' => 'required|string|max:255',
        ]);

        $signingDoctor->update($request->only('name', 'rank', 'afpos', 'status', 'position'));

        return Redirect::back()->with('success', 'Signing doctor updated successfully.');
    }

    public function destroy(SigningDoctor $signingDoctor)
    {
        $signingDoctor->delete();

        return Redirect::back()->with('success', 'Signing doctor deleted successfully.');
    }

    public function index()
    {
        $signingDoctors = SigningDoctor::orderBy('name')->get();
        return response()->json($signingDoctors);
    }
}
