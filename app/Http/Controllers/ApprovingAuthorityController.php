<?php

namespace App\Http\Controllers;

use App\Models\ApprovingAuthority;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ApprovingAuthorityController extends Controller
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

        $data = $request->only('name', 'rank', 'afpos', 'status', 'position');
        
        if ($request->hasFile('e_signature')) {
            $file = $request->file('e_signature');
            if ($file->isValid() && $file->getClientOriginalExtension() === 'png') {
                $data['e_signature_path'] = $file->store('signatures', 'public');
            }
        }
        
        ApprovingAuthority::create($data);

        return Redirect::back()->with('success', 'Approving authority created successfully.');
    }

    public function update(Request $request, ApprovingAuthority $approvingAuthority)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rank' => 'required|string|max:255',
            'afpos' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'position' => 'required|string|max:255',
        ]);

        $data = $request->only('name', 'rank', 'afpos', 'status', 'position');

        if ($request->hasFile('e_signature')) {
            $file = $request->file('e_signature');
            if ($file->isValid() && in_array(strtolower($file->getClientOriginalExtension()), ['png', 'jpg', 'jpeg'])) {
                $data['e_signature_path'] = $file->store('signatures', 'public');
            }
        }

        $approvingAuthority->update($data);

        return Redirect::back()->with('success', 'Approving authority updated successfully.');
    }

    public function destroy(ApprovingAuthority $approvingAuthority)
    {
        $approvingAuthority->delete();

        return Redirect::back()->with('success', 'Approving authority deleted successfully.');
    }

    public function setAsDefault($id)
    {
        ApprovingAuthority::query()->update(['is_default' => false]);
        
        if ($id !== 'chief_doctor') {
            $approvingAuthority = ApprovingAuthority::findOrFail($id);
            $approvingAuthority->update(['is_default' => true]);
        }

        return Redirect::back()->with('success', 'Default Approving Authority updated.');
    }

    public function index()
    {
        return response()->json(ApprovingAuthority::orderBy('name')->get());
    }
}