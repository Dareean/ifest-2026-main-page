<?php

namespace App\Http\Controllers;

use App\Models\TimelineEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimelineController extends Controller
{
    public function index(): JsonResponse
    {
        $events = TimelineEvent::where('is_active', true)->orderBy('order')->get();
        return response()->json(['data' => $events]);
    }

    public function adminIndex(): JsonResponse
    {
        $events = TimelineEvent::orderBy('order')->get();
        return response()->json(['data' => $events]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phase' => 'required|string|max:10',
            'title' => 'required|string|max:255',
            'date_range' => 'required|string|max:255',
            'description_items' => 'nullable|array',
            'description_items.*' => 'string',
            'accent_color' => 'nullable|string|max:20',
            'status' => 'nullable|string|in:upcoming,ongoing,completed',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $event = TimelineEvent::create($validator->validated());

        return response()->json([
            'message' => 'Event timeline berhasil ditambahkan',
            'data' => $event,
        ], 201);
    }

    public function show(TimelineEvent $timelineEvent): JsonResponse
    {
        return response()->json(['data' => $timelineEvent]);
    }

    public function update(Request $request, TimelineEvent $timelineEvent): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phase' => 'sometimes|string|max:10',
            'title' => 'sometimes|string|max:255',
            'date_range' => 'sometimes|string|max:255',
            'description_items' => 'nullable|array',
            'description_items.*' => 'string',
            'accent_color' => 'nullable|string|max:20',
            'status' => 'nullable|string|in:upcoming,ongoing,completed',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $timelineEvent->update($validator->validated());

        return response()->json([
            'message' => 'Event timeline berhasil diperbarui',
            'data' => $timelineEvent->fresh(),
        ]);
    }

    public function destroy(TimelineEvent $timelineEvent): JsonResponse
    {
        $timelineEvent->delete();

        return response()->json([
            'message' => 'Event timeline berhasil dihapus',
        ]);
    }
}
