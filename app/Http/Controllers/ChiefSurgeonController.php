<?php

namespace App\Http\Controllers;

use App\Models\ChiefSurgeon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ChiefSurgeonController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rank' => 'nullable|string|max:255',
            'afpos' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        $data = $request->only('name', 'rank', 'afpos', 'status', 'position');
        
        if ($request->hasFile('e_signature')) {
            $file = $request->file('e_signature');
            if ($file->isValid() && $file->getClientOriginalExtension() === 'png') {
                $data['e_signature_path'] = $file->store('signatures', 'public');
            }
        }
        
        ChiefSurgeon::create($data);

        return Redirect::back()->with('success', 'Chief surgeon created successfully.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rank' => 'nullable|string|max:255',
            'afpos' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        $officer = ChiefSurgeon::findOrFail($id);
        $data = $request->only('name', 'rank', 'afpos', 'status', 'position');

        if ($request->hasFile('e_signature')) {
            $file = $request->file('e_signature');
            if ($file->isValid() && in_array(strtolower($file->getClientOriginalExtension()), ['png', 'jpg', 'jpeg'])) {
                $data['e_signature_path'] = $file->store('signatures', 'public');
            }
        }

        $officer->update($data);

        return Redirect::back()->with('success', 'Chief surgeon updated successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $officer = ChiefSurgeon::findOrFail($id);
        $officer->delete();

        return Redirect::back()->with('success', 'Chief surgeon deleted successfully.');
    }

    public function setAsDefault($id): RedirectResponse
    {
        $officer = ChiefSurgeon::findOrFail($id);
        
        // Unset all other defaults
        ChiefSurgeon::where('id', '!=', $id)->update(['is_default' => false]);
        
        // Set this as default
        $officer->update(['is_default' => true]);

        return Redirect::back()->with('success', 'Chief surgeon set as default successfully.');
    }

    public function index()
    {
        return response()->json(ChiefSurgeon::orderBy('name')->get());
    }
}