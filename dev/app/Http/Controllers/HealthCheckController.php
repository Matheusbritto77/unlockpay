<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;


use OpenApi\Annotations as OA;









class HealthCheckController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/health",
     *     summary="Verifica a saúde da API",
     *     tags={"Saúde da API"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="API está funcionando corretamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2025-03-31T12:00:00Z")
     *         )
     *     )
     * )
     */
    public function healthCheck(): JsonResponse
    {
        return response()->json([
            'status' => 'UP',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/version",
     *     summary="Obtém a versão da API",
     *     tags={"Versão"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Retorna a versão da API",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="version", type="string", example="1.0.0")
     *         )
     *     )
     * )
     */
    public function getVersion(): JsonResponse
    {
        return response()->json([
            'version' => '1.1.0.8'
        ]);
    }
}

