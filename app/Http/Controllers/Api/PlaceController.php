<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\JsonResponse;

class PlaceController extends Controller
{
    public function index(): JsonResponse
    {
        $places = Place::query()
            ->orderBy('place_name')
            ->pluck('place_name');

        return response()->json($places);
    }
}
