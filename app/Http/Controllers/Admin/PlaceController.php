<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlaceRequest;
use App\Models\Place;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    public function store(StorePlaceRequest $request)
    {
        $validated = $request->validate([
            'place_name' => ['required', 'string', 'max:255'],
        ]);

        Place::create([
            'place_name' => trim($validated['place_name']),
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Place created successfully.');
    }

    public function destroy(int $placeId)
    {
        Place::findOrFail($placeId)->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Place deleted successfully.');
    }
}
