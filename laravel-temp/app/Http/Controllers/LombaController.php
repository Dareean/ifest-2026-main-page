<?php

namespace App\Http\Controllers;

use App\Models\Lomba;
use Illuminate\Http\JsonResponse;

class LombaController extends Controller
{
    public function index(): JsonResponse
    {
        $lombas = Lomba::where('is_active', true)->get();

        return response()->json(['data' => $lombas]);
    }

    public function show(Lomba $lomba): JsonResponse
    {
        return response()->json(['data' => $lomba]);
    }
}
