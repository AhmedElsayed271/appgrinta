<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranslateController extends Controller
{
    /**
     * Translate a single string to the target language.
     */
    public function translate(Request $request): JsonResponse
    {
        $request->validate([
            'text'   => ['required', 'string'],
            'target' => ['required', 'string', 'max:20'],
            'source' => ['nullable', 'string', 'max:20'],
        ]);

        $translated = app(TranslationService::class)->translate(
            $request->input('text'),
            $request->input('source', 'ar'),
            $request->input('target', 'en')
        );

        if ($translated === null) {
            return response()->json([
                'success' => false,
                'message' => 'فشلت الترجمة من جميع المصادر، حاول مرة أخرى بعد قليل',
            ]);
        }

        return response()->json([
            'success'     => true,
            'translation' => $translated,
        ]);
    }
}