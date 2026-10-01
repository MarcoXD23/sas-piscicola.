<?php

namespace App\Http\Controllers;

use App\Services\GeminiAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeminiAiController extends Controller
{
    public function __construct(
        protected GeminiAiService $geminiService
    ) {}

    /**
     * Procesa la consulta del usuario con el Asistente Experto de IA (Google Gemini).
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:3000'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,model,assistant'],
            'history.*.text' => ['required_with:history', 'string'],
        ]);

        $message = $validated['message'];
        $history = $validated['history'] ?? [];

        $result = $this->geminiService->ask($message, $history);

        $now = now()->setTimezone('America/Bogota');

        return response()->json([
            'status' => 'success',
            'reply' => $result['reply'],
            'model' => $result['model'],
            'is_simulated' => $result['is_simulated'],
            'timestamp' => $now->format('g:i:s A'),
            'time_12h' => $now->format('g:i A'),
        ]);
    }
}
