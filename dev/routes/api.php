<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\InstanciaController;
use App\Http\Controllers\HistoricoPagController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\ChatP2PController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\TestPointController;
use App\Http\Controllers\PeliculaController;








Route::get('/user', function (Request $request) {
    return $request->user();
    
    

})->middleware('auth:sanctum');





Route::post('/clientes', [ClienteController::class, 'create'])->middleware('auth:sanctum');
Route::post('/cobrar', [HistoricoPagController::class, 'cobrar'])->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->get('/clientes/list', [ClienteController::class, 'index']);

Route::middleware('auth:sanctum')->get('/testpoint/list', [TestPointController::class, 'listarMarcasModelos']);



Route::middleware(['auth:sanctum'])->group(function () {
   
  
    Route::get('/clientes/search', [ClienteController::class, 'searchClientes'])->middleware('auth:sanctum');
    
    
    Route::get('/clientes/total', [ClienteController::class, 'totalClientes'])->middleware('auth:sanctum');


    Route::get('/pagamentos/ultimas-24h', [PagamentoController::class, 'index']);
    
    Route::post('/pagamentos/atualizar-status', [PagamentoController::class, 'atualizarStatus']);

    Route::middleware('auth:sanctum')->get('/estatisticas-pagamentos', [DashboardController::class, 'estatisticasPagamentos']);
    
    Route::middleware('auth:sanctum')->delete('/clientes/delete/{id}', [ClienteController::class, 'deleteCliente']);
    
    Route::post('/pagamentos', [HistoricoPagController::class, 'register']);
    Route::get('/pagamentos/resumo', [HistoricoPagController::class, 'resumoPagamentos']);
     
    
    Route::get('/pesquisar', [TestPointController::class, 'pesquisarTempoReal']);
    
    Route::get('peliculas', [PeliculaController::class, 'getAllPeliculas']);
    Route::get('peliculas/search', [PeliculaController::class, 'search']);
    
    
    

Route::prefix('chat-p2p')->group(function () {
    Route::get('/', [ChatP2PController::class, 'index']);  // Lista de peers (paginada)
    Route::post('/', [ChatP2PController::class, 'store']); // Criação/atualização de peer
});

});

Route::middleware('auth:sanctum')->get('/resumo-pagamentos', [ResumeController::class, 'getResumoPorPeriodo']);


// Definição das rotas protegidas pelo Sanctum
Route::middleware('auth:sanctum')->group(function () {
    });

Route::get('/version', [HealthCheckController::class, 'getVersion']);
Route::get('/health', [HealthCheckController::class, 'healthCheck']);
    


Route::middleware('auth:sanctum')->delete('/historico-pagamentos/{id}', [HistoricoPagController::class, 'delete']);

Route::middleware('auth:sanctum')->get('/historico-pagamentos', [HistoricoPagController::class, 'listarHistoricoPagamentos']);
Route::post('/webhook-pagamento', [HistoricoPagController::class, 'webhookPagamento']);


 Route::post('/login', [LoginController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/categorias', [CategoriaController::class, 'register']);
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::put('/categorias/{id}', [CategoriaController::class, 'update']);
    Route::delete('/categorias/{id}', [CategoriaController::class, 'delete']);
});


Route::put('/clientes/{id}', [ClienteController::class, 'update'])->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/instancias/index', [InstanciaController::class, 'index']);
    Route::get('/instancias', [InstanciaController::class, 'store']);
    Route::get('/instancias/{id}', [InstanciaController::class, 'show']);
    Route::post('/instancias/destroy', [InstanciaController::class, 'destroy']);
    Route::put('/instancias/{id}/activate', [InstanciaController::class, 'activate']);
    Route::put('/instancias/{id}/deactivate', [InstanciaController::class, 'deactivate']);
});

Route::post('/mercadopago/callback', [PagamentoController::class, 'callback']);