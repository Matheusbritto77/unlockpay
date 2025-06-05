<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="API do Sistema TEAM",
 *     description="API para autenticação de usuários do Sistema TEAM, incluindo login e geração de tokens de acesso.",
 *     version="1.0.0",
 *     @OA\Contact(
 *         email="suporte@sitesma.com"
 *     )
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="BearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Autenticação via Bearer Token (JWT)"
 * )
 */
abstract class Controller
{
    // Esse controlador pode ser estendido por outros controladores para herdar as anotações de API
}

