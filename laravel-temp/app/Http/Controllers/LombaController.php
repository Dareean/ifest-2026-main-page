<?php

namespace App\Http\Controllers;

use App\Models\Lomba;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class LombaController extends Controller
{
    public function index(): JsonResponse
    {
        $lombas = Cache::remember('active_lombas', 300, function () {
            return Lomba::where('is_active', true)->get();
        });

        return response()->json(['data' => $lombas]);
    }

    public function show(Lomba $lomba): JsonResponse
    {
        return response()->json(['data' => $lomba]);
    }
}
