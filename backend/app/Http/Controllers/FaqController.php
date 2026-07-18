<?php

namespace App\Http\Controllers;

use App\Models\FaqItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FaqController extends Controller
{
    public function index(): JsonResponse
    {
        $faqs = FaqItem::where('is_active', true)->orderBy('order')->get();
        return response()->json(['data' => $faqs]);
    }

    public function adminIndex(): JsonResponse
    {
        $faqs = FaqItem::orderBy('order')->get();
        return response()->json(['data' => $faqs]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:5000',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $faq = FaqItem::create($validator->validated());

        return response()->json([
            'message' => 'FAQ berhasil ditambahkan',
            'data' => $faq,
        ], 201);
    }

    public function show(FaqItem $faqItem): JsonResponse
    {
        return response()->json(['data' => $faqItem]);
    }

    public function update(Request $request, FaqItem $faqItem): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question' => 'sometimes|string|max:500',
            'answer' => 'sometimes|string|max:5000',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $faqItem->update($validator->validated());

        return response()->json([
            'message' => 'FAQ berhasil diperbarui',
            'data' => $faqItem->fresh(),
        ]);
    }

    public function destroy(FaqItem $faqItem): JsonResponse
    {
        $faqItem->delete();

        return response()->json([
            'message' => 'FAQ berhasil dihapus',
        ]);
    }
}
