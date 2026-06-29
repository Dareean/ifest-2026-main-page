<?php

namespace App\Http\Controllers;

use App\Models\Lomba;
use App\Models\Pendaftaran;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PendaftaranController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $pendaftarans = $request->user()->pendaftarans()->with('lomba', 'submission')->latest()->get();

        return response()->json(['data' => $pendaftarans]);
    }

    public function store(Request $request, Lomba $lomba): JsonResponse
    {
        $exists = Pendaftaran::where('user_id', $request->user()->id)
            ->where('lomba_id', $lomba->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Kamu sudah terdaftar di lomba ini'], 409);
        }

        $validator = Validator::make($request->all(), [
            'team_name' => 'nullable|string|max:255',
            'team_members' => 'nullable|array',
            'team_members.*.name' => 'required_with:team_members|string|max:255',
            'team_members.*.email' => 'required_with:team_members|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pendaftaran = Pendaftaran::create([
            'user_id' => $request->user()->id,
            'lomba_id' => $lomba->id,
            'team_name' => $request->team_name,
            'team_members' => $request->team_members,
        ]);

        Notification::create([
            'user_id' => $request->user()->id,
            'judul' => 'Pendaftaran Berhasil',
            'pesan' => "Kamu berhasil mendaftar di lomba {$lomba->title}. Tim kami akan memverifikasi pendaftaranmu.",
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil',
            'data' => $pendaftaran->load('lomba'),
        ], 201);
    }

    public function show(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        if ($pendaftaran->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $pendaftaran->load('lomba', 'submission')]);
    }
}
