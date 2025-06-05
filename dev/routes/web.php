<?php
use App\Http\Controllers\DownloadController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PeliculaController;
use App\Http\Controllers\ResellerController;
use App\Http\Controllers\SaqueController;
use App\Http\Middleware\HandleReferral;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\PlanosController;
use App\Http\Controllers\ScheduleController;

Route::get('/download', [DownloadController::class, 'download'])->name('download');
Route::get('/schedules/check', [ScheduleController::class, 'processScheduledPosts'])->name('schedules.process');



Route::any('/pagamento/qrcode/{plan_id}', [PagamentoController::class, 'criarPagamentoComQrCode']);


Route::get('/checa-planos', [PlanosController::class, 'checkExpiredPlans']);

Route::get('/pagamento/status/{reference}', [PagamentoController::class, 'statusPorReferenciaDireta']);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified', 'verifica.plano',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/agenda', function () {
        return Inertia::render('Agenda');
    })->name('agenda');

    Route::get('/groups', [ScheduleController::class, 'fetchGroupsFromApi']);

    Route::get('/reseller', function () {
        return Inertia::render('DashboardReseller');
    })->name('reseller')->middleware('auth');

    Route::get('/estatisticas-pagamentos', [DashboardController::class, 'estatisticasPagamentos']);
    Route::get('/saque', [SaqueController::class, 'sacar']);

    Route::get('/clientes-indicados', [ResellerController::class, 'contarClientesIndicados'])
     ->middleware('auth')
     ->name('clientes.indicados');

    Route::middleware('auth')->get('/meu-plano', [PlanosController::class, 'meuPlano']);
    Route::put('/user/chave-pix', [ResellerController::class, 'updateChavePix'])->name('user-chave-pix.update');
    
Route::get('/reseller-info', [ResellerController::class, 'getResellerInfo'])->middleware('auth');



 Route::get('/schedules', [ScheduleController::class, 'index']);

    // Mostrar detalhes de um agendamento específico
    Route::get('/schedules/{schedule}', [ScheduleController::class, 'show']);

    // Criar um novo agendamento
    Route::post('/schedules', [ScheduleController::class, 'store']);

    // Atualizar um agendamento existente
    Route::post('/schedules/{schedule}', [ScheduleController::class, 'update']);

    // Excluir um agendamento
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy']);

    // Alternar o status de um agendamento (ativo/inativo)
    Route::patch('/schedules/{schedule}/toggle-status', [ScheduleController::class, 'toggleStatus']);

    // Buscar grupos da API externa
    


});


// Rota aberta para a página de planos
Route::get('/planos', function() {
    // Se usa Inertia:
    return Inertia::render('Planos');
})->name('planos');




Route::post('/decrypt-cookie', function (Request $request) {
    $cookie = $request->input('cookie');
    try {
        $decryptedCookie = Crypt::decryptString($cookie);
        return response()->json($decryptedCookie);
    } catch (\Exception $e) {
        return response()->json(null);
    }
})->name('decrypt-cookie');






Route::get('/{codigo}', function () {
    return redirect()->route('register');
})->middleware('referral');













Route::middleware(['auth', 'admin'])->group(function () {
    
    Route::get('admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/test-point/criar', [AdminController::class, 'criarTestPoint'])->name('test-point.criar');
Route::get('admin/marcas', [AdminController::class, 'listarMarcas'])->name('marcas.lista');
Route::post('/marca/criar', [AdminController::class, 'criarMarca'])->name('api.marca.criar');

Route::get('admin/list', [AdminController::class, 'listarMarcasModelos'])->name('listMarcasModelos');
Route::post('/admin/marca/editar', [AdminController::class, 'editarMarca'])->name('admin.marca.editar');
Route::post('/admin/marca/deletar', [AdminController::class, 'deletarMarca'])->name('admin.marca.deletar');
Route::post('/admin/testpoint/editar', [AdminController::class, 'editarModelo'])->name('admin.testpoint.editar');
Route::post('/admin/testpoint/deletar', [AdminController::class, 'deletarModelo'])->name('admin.testpoint.deletar');
Route::get('/admin/pesquisar', [AdminController::class, 'pesquisarTempoReal'])->name('admin.pesquisar');

// Rotas para o CRUD de Peliculas
Route::get('/admin/peliculas', [PeliculaController::class, 'index'])->name('peliculas.index');
Route::get('/admin/peliculas/create', [PeliculaController::class, 'create'])->name('peliculas.create');
Route::post('/admin/peliculas', [PeliculaController::class, 'store'])->name('peliculas.store');

Route::get('/admin/peliculas/{id}/edit', [PeliculaController::class, 'edit'])->name('peliculas.edit');
Route::put('/admin/peliculas/{id}', [PeliculaController::class, 'update'])->name('peliculas.update');
Route::get('peliculas/search', [PeliculaController::class, 'search'])->name('peliculas.search');

Route::delete('/admin/peliculas/{id}', [PeliculaController::class, 'destroy'])->name('peliculas.destroy');


Route::get('/admin/listar-usuarios', [AdminController::class, 'listarUsuarios'])->name('admin.listarUsuarios');


Route::any('/admin/alterar-porcentagem-reseller', [AdminController::class, 'alterarPorcentagemReseller']);


Route::get('/admin/criar-marca', function () {
    return view('admin.criar_marca');
})->name('admin.criar_marca');
   
});








Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('/howto', function () {
    return Inertia::render('HowTo');
})->name('howto');


Route::get('/plans/sobre', function () {
    return Inertia::render('SobrePlans');
})->name('SobrePlans');















