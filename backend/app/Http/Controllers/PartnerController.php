<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartnerController extends Controller
{
    public function index(): JsonResponse
    {
        $partners = Partner::where('is_active', true)->orderBy('order')->get();
        return response()->json(['data' => $partners]);
    }

    public function adminIndex(): JsonResponse
    {
        $partners = Partner::orderBy('order')->get();
        return response()->json(['data' => $partners]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:main_strategic,strategic_partner,media_partner,organizer,sponsorship_tier',
            'name' => 'required|string|max:255',
            'logo_url' => 'required|string|max:1000',
            'instagram_url' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:2000',
            'tier_data' => 'nullable|array',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $partner = Partner::create($request->only([
            'type', 'name', 'logo_url', 'instagram_url', 'description', 'tier_data', 'order', 'is_active'
        ]));

        return response()->json([
            'message' => 'Partner berhasil ditambahkan',
            'data' => $partner
        ], 201);
    }

    public function show(Partner $partner): JsonResponse
    {
        return response()->json(['data' => $partner]);
    }

    public function update(Request $request, Partner $partner): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:main_strategic,strategic_partner,media_partner,organizer,sponsorship_tier',
            'name' => 'required|string|max:255',
            'logo_url' => 'required|string|max:1000',
            'instagram_url' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:2000',
            'tier_data' => 'nullable|array',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $partner->update($request->only([
            'type', 'name', 'logo_url', 'instagram_url', 'description', 'tier_data', 'order', 'is_active'
        ]));

        return response()->json([
            'message' => 'Partner berhasil diperbarui',
            'data' => $partner->fresh()
        ]);
    }

    public function destroy(Partner $partner): JsonResponse
    {
        $partner->delete();
        return response()->json([
            'message' => 'Partner berhasil dihapus'
        ]);
    }
}
