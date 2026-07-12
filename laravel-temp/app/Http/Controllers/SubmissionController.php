<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Pendaftaran;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubmissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $submissions = Submission::whereHas('pendaftaran', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->with('pendaftaran.lomba')->latest()->get();

        return response()->json(['data' => $submissions]);
    }

    public function store(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        if ($pendaftaran->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($pendaftaran->status !== 'verified') {
            return response()->json(['message' => 'Pendaftaran belum diverifikasi'], 400);
        }

        if (!$pendaftaran->lomba->is_submission_open) {
            return response()->json(['message' => 'Pengumpulan karya untuk lomba ini sudah ditutup'], 403);
        }

        $validator = Validator::make($request->all(), [
            'link_drive' => 'required|url|max:500',
            'catatan' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $submission = Submission::updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'link_drive' => $request->link_drive,
                'catatan' => $request->catatan,
                'status' => 'submitted',
            ]
        );

        Notification::create([
            'user_id' => $request->user()->id,
            'judul' => 'Karya Berhasil Dikumpulkan',
            'pesan' => "Karyamu untuk lomba {$pendaftaran->lomba->title} berhasil dikumpulkan. Tim juri akan segera mereview.",
        ]);

        return response()->json([
            'message' => 'Karya berhasil dikumpulkan',
            'data' => $submission,
        ], 201);
    }
}
