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
            'gelombang_1_start' => 'nullable|date',
            'gelombang_1_end' => 'nullable|date',
            'gelombang_2_end' => 'nullable|date',
            'fee_gelombang_1' => 'nullable|string|max:100',
            'fee_gelombang_2' => 'nullable|string|max:100',
            'registration_link' => 'nullable|string|max:500',
            'guidebook_link' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lomba->update($request->only([
            'gelombang_1_start', 'gelombang_1_end', 'gelombang_2_end',
            'fee_gelombang_1', 'fee_gelombang_2',
            'registration_link', 'guidebook_link', 'contact_person',
        ]));

        return response()->json([
            'message' => 'Data lomba berhasil diperbarui',
            'data' => $lomba->fresh(),
        ]);
    }
}
