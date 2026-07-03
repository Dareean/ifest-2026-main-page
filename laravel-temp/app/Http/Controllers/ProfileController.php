<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'institution' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update($request->only(['name', 'phone', 'institution']));

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user'    => $user,
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // max 2MB
        ], [
            'avatar.image'    => 'File harus berupa gambar.',
            'avatar.mimes'    => 'Format yang didukung: JPEG, PNG, JPG, WEBP.',
            'avatar.max'      => 'Ukuran foto maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        // Hapus file avatar lama jika ada dan bukan URL Google/eksternal
        if ($user->avatar && str_starts_with($user->avatar, '/storage/')) {
            $oldPath = str_replace('/storage/', 'public/', $user->avatar);
            Storage::delete($oldPath);
        }

        // Simpan file baru ke storage/app/public/avatars
        $file = $request->file('avatar');
        $path = $file->store('public/avatars');
        $publicUrl = '/storage/' . str_replace('public/', '', $path);

        $user->update(['avatar' => $publicUrl]);

        return response()->json([
            'message' => 'Foto profil berhasil diperbarui',
            'user'    => $user,
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $hasGoogleId = !empty($user->google_id);

        $rules = [
            'new_password' => 'required|string|min:8|confirmed',
        ];

        if (!$hasGoogleId) {
            $rules['current_password'] = 'required|string';
        } else {
            $rules['current_password'] = 'nullable|string';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Password saat ini salah'], 400);
            }
        } else {
            if (!$hasGoogleId) {
                return response()->json(['message' => 'Password saat ini diperlukan'], 400);
            }
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password berhasil diubah']);
    }
}
