<?php

namespace App\Http\Controllers;

use App\Exceptions\AssistantIndisponibleException;
use App\Http\Requests\AssistantRedactionRequest;
use App\Services\AssistantRedactionService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AssistantRedactionController extends Controller
{
    /**
     * Traite la demande de reformulation IA de la description citoyenne.
     */
    public function __invoke(AssistantRedactionRequest $request, AssistantRedactionService $assistantService): JsonResponse
    {
        abort_unless($request->user()?->role === 'user', 403);

        try {
            $suggestion = $assistantService->reformuler(
                texte: $request->validated('texte'),
                typeSignalement: $request->validated('type_signalement'),
                typeIncident: $request->validated('type_incident')
            );

            return response()->json([
                'suggestion' => $suggestion,
            ], 200);
        } catch (AssistantIndisponibleException) {
            return response()->json([
                'message' => "L'assistant est indisponible, vous pouvez continuer à écrire votre description.",
            ], 503);
        } catch (Throwable) {
            return response()->json([
                'message' => "L'assistant est indisponible, vous pouvez continuer à écrire votre description.",
            ], 503);
        }
    }
}

