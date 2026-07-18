<?php

namespace App\Http\Controllers;

use App\Models\Lomba;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminLombaController extends Controller
{
    public function index(): JsonResponse
    {
        $lombas = Lomba::withCount('pendaftarans')->get();
        return response()->json(['data' => $lombas]);
    }

    public function toggleSubmission(Lomba $lomba): JsonResponse
    {
        $lomba->update([
            'is_submission_open' => !$lomba->is_submission_open,
        ]);

        $status = $lomba->fresh()->is_submission_open ? 'dibuka' : 'ditutup';

        return response()->json([
            'message' => "Pengumpulan karya untuk {$lomba->title} berhasil {$status}",
            'data' => $lomba->fresh(),
        ]);
    }

    public function toggleActive(Lomba $lomba): JsonResponse
    {
        $lomba->update([
            'is_active' => !$lomba->is_active,
        ]);

        $status = $lomba->fresh()->is_active ? 'ditampilkan' : 'disembunyikan';

        return response()->json([
            'message' => "Lomba {$lomba->title} berhasil {$status}",
            'data' => $lomba->fresh(),
        ]);
    }

    public function update(Request $request, Lomba $lomba): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'scale' => 'sometimes|string|max:100',
            'tagline' => 'sometimes|string|max:255',
            'fee' => 'sometimes|string|max:100',
            'target' => 'sometimes|string|max:255',
            'team_requirements' => 'sometimes|string|max:100',
            'languages' => 'sometimes|string|max:255',
            'babak' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:500',
            'long_description' => 'sometimes|string',
            'rules' => 'nullable|array',
            'rules.*' => 'string|max:1000',
            'schedule' => 'sometimes|string|max:255',
            'sub_themes' => 'nullable|array',
            'sub_themes.*' => 'string|max:500',
            'registration_link' => 'nullable|string|max:500',
            'guidebook_link' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:200',
            'card_bg' => 'sometimes|string|max:20',
            'accent_color' => 'sometimes|string|max:20',
            'text_color' => 'sometimes|string|max:20',
            'gelombang_1_start' => 'nullable|date',
            'gelombang_1_end' => 'nullable|date',
            'gelombang_2_end' => 'nullable|date',
            'fee_gelombang_1' => 'nullable|string|max:100',
            'fee_gelombang_2' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lomba->update($request->only([
            'title', 'scale', 'tagline', 'fee', 'target',
            'team_requirements', 'languages', 'babak',
            'description', 'long_description', 'rules',
            'schedule', 'sub_themes',
            'registration_link', 'guidebook_link', 'contact_person',
            'card_bg', 'accent_color', 'text_color',
            'gelombang_1_start', 'gelombang_1_end', 'gelombang_2_end',
            'fee_gelombang_1', 'fee_gelombang_2',
        ]));

        return response()->json([
            'message' => 'Data lomba berhasil diperbarui',
            'data' => $lomba->fresh(),
        ]);
    }
}
