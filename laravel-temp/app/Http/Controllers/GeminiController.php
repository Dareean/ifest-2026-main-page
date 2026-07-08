<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'messages' => 'required|array|max:50',
            'messages.*.role' => 'required|string|in:user,model',
            'messages.*.text' => 'required|string|max:4000',
            'systemInstruction' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            Log::error('Gemini API key not configured');
            return response()->json(['message' => 'AI service not configured'], 500);
        }

        $contents = [];
        foreach ($request->messages as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'model' ? 'model' : 'user',
                'parts' => [['text' => $msg['text']]],
            ];
        }

        $body = [
            'contents' => $contents,
        ];

        if ($request->filled('systemInstruction')) {
            $body['system_instruction'] = [
                'parts' => [['text' => $request->systemInstruction]],
            ];
        }

        try {
            $response = Http::timeout(30)->withHeaders([
                'X-Goog-Api-Key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent', $body);

            if (!$response->successful()) {
                Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json(['message' => 'AI service error'], 502);
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return response()->json(['text' => $text]);
        } catch (\Exception $e) {
            Log::error('Gemini API call failed: ' . $e->getMessage());
            return response()->json(['message' => 'AI service unavailable'], 502);
        }
    }
}
