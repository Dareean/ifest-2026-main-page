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
        $user = $request->user();
        
        $owned = Pendaftaran::where('user_id', $user->id)
            ->with('lomba', 'submission', 'user')
            ->get();
            
        $memberOf = Pendaftaran::where('status', 'verified')
            ->whereNotNull('team_members')
            ->with('lomba', 'submission', 'user')
            ->get()
            ->filter(function ($p) use ($user) {
                $mList = $p->team_members ?: [];
                foreach ($mList as $m) {
                    if (isset($m['email']) && strtolower($m['email']) === strtolower($user->email) && isset($m['status']) && $m['status'] === 'joined') {
                        return true;
                    }
                }
                return false;
            })
            ->values();

        $all = $owned->merge($memberOf)->sortByDesc('created_at')->values();

        return response()->json(['data' => $all]);
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
        $user = $request->user();
        $isLeader = $pendaftaran->user_id === $user->id;
        $isMember = false;
        
        $mList = $pendaftaran->team_members ?: [];
        foreach ($mList as $m) {
            if (isset($m['email']) && strtolower($m['email']) === strtolower($user->email) && isset($m['status']) && $m['status'] === 'joined') {
                $isMember = true;
                break;
            }
        }

        if (!$isLeader && !$isMember) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $pendaftaran->load('lomba', 'submission', 'user')]);
    }
}
