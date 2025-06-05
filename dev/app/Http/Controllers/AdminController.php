<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function index()
{
    $totalUsuarios = User::count();
    $user = Auth::user();

    // Conta usuários com plan_id entre 2 e 5 (inclusive)
    $totalUsuariosPlanos25 = User::whereBetween('plan_id', [2, 5])->count();

    return view('dashboardadmin', [
        'user' => $user,
        'totalUsuarios' => $totalUsuarios,
        'totalUsuariosPlanos25' => $totalUsuariosPlanos25,
    ]);
}
    // Função para criar uma nova marca (pasta no storage/testpoint)
public function criarMarca(Request $request)
{
    $request->validate([
        'marca' => 'required|string', // Removido unique:brands,name pois não usamos tabela
    ]);

    $marca = Str::slug($request->input('marca')); // Converte para slug para evitar problemas com espaços ou caracteres especiais
    $basePath = "testpoint";
    $path = "{$basePath}/{$marca}";

    // Verifica se a pasta base 'testpoint' existe, se não, cria
    if (!Storage::exists($basePath)) {
        Storage::makeDirectory($basePath);
    }

    // Cria a pasta da marca no storage se não existir
    if (!Storage::exists($path)) {
        Storage::makeDirectory($path);
        return response()->json([
            'message' => "Marca '{$request->input('marca')}' criada com sucesso!",
            'marca' => $marca
        ], 201); // Status 201: Created
    }

    return response()->json([
        'error' => "Marca '{$request->input('marca')}' já existe!"
    ], 409); // Status 409: Conflict
}

    // Função para criar um test point (salvar imagem na pasta da marca com nome do modelo)
    public function criarTestPoint(Request $request)
    {
        $request->validate([
            'marca' => 'required|string',
            'modelo' => 'required|string',
            'imagem' => 'required|image|mimes:jpeg,png,jpg,gif,webp,|max:2048', // Limite de 2MB para imagem
        ]);

        $marca = Str::slug($request->input('marca'));
        $modelo = Str::slug($request->input('modelo'));
        $path = "testpoint/{$marca}";

        // Verifica se a pasta da marca existe
        if (!Storage::exists($path)) {
            return response()->json([
                'error' => "Marca '{$request->input('marca')}' não encontrada!"
            ], 404); // Status 404: Not Found
        }

        // Salva a imagem com o nome do modelo
        $imagem = $request->file('imagem');
        $extensao = $imagem->getClientOriginalExtension();
        $nomeImagem = "{$modelo}.{$extensao}";
        $imagemPath = $path . '/' . $nomeImagem;

        // Verifica se já existe uma imagem com esse nome
        if (Storage::exists($imagemPath)) {
            return response()->json([
                'error' => "Já existe um modelo com o nome '{$modelo}' nesta marca!"
            ], 409); // Status 409: Conflict
        }

        // Salva a imagem no storage
        Storage::putFileAs($path, $imagem, $nomeImagem);

        return response()->json([
            'message' => "Test Point '{$modelo}' criado com sucesso na marca '{$request->input('marca')}'!",
            'marca' => $marca,
            'modelo' => $modelo,
            'imagem' => $nomeImagem
        ], 201); // Status 201: Created
    }

   // No controlador que renderiza a página principal (ex: AdminController)
public function listarMarcas()
{
    // Reutilizando a lógica de listarMarcas
    $marcas = collect(Storage::directories('testpoint'))
        ->map(function ($dir) {
            return basename($dir);
        });

    return view('admin.modal.criar_testpoint', compact('marcas'));
}







// No controlador AdminController.php
public function listarMarcasModelos()
{
    // Extensões de imagem suportadas
    $extensoesSuportadas = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];

    $marcas = collect(Storage::directories('testpoint'))
        ->mapWithKeys(function ($dir) use ($extensoesSuportadas) {
            $marca = basename($dir);
            $modelos = collect(Storage::files($dir))
                ->filter(function ($file) use ($extensoesSuportadas) {
                    // Filtra apenas arquivos com extensões de imagem suportadas
                    $extensao = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    return in_array($extensao, $extensoesSuportadas);
                })
                ->map(function ($file) use ($marca) {
                    $nomeArquivo = basename($file);
                    $caminhoArquivo = "app/public/testpoint/$marca/$nomeArquivo";
                    
                    // Gera a URL base usando asset() para o diretório public
                    $urlRelativa = asset($caminhoArquivo);
                    
                    // Monta a URL completa com o domínio da aplicação (opcional, já que asset() gera URL absoluta)
                    $urlCompleta = url($urlRelativa);

                    return [
                        'modelo' => pathinfo($nomeArquivo, PATHINFO_FILENAME), // Nome do modelo sem extensão
                        'imagem_url' => $urlCompleta, // URL completa para a imagem
                    ];
                });
            return [$marca => $modelos];
        });

    // Retorna a view com os dados das marcas e modelos
    return view('admin.TestPointList', compact('marcas'));
}




public function pesquisarTempoReal(Request $request)
{
    $termo = strtolower(trim($request->input('termo', '')));
    $extensoesSuportadas = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];

    if (empty($termo)) {
        // Se não houver termo, retorna todas as marcas e modelos
        $resultados = collect(Storage::directories('testpoint'))
            ->mapWithKeys(function ($dir) use ($extensoesSuportadas) {
                $marca = basename($dir);
                $modelos = collect(Storage::files($dir))
                    ->filter(function ($file) use ($extensoesSuportadas) {
                        $extensao = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        return in_array($extensao, $extensoesSuportadas);
                    })
                    ->map(function ($file) use ($marca) {
                        $nomeArquivo = basename($file);
                        $caminhoArquivo = "app/public/testpoint/$marca/$nomeArquivo";
                        $urlRelativa = asset($caminhoArquivo);
                        $urlCompleta = url($urlRelativa);

                        return [
                            'modelo' => pathinfo($nomeArquivo, PATHINFO_FILENAME),
                            'imagem_url' => $urlCompleta,
                        ];
                    });
                return [$marca => $modelos];
            });
    } else {
        // Se houver termo, filtra por marca ou modelo
        $resultados = collect(Storage::directories('testpoint'))
            ->mapWithKeys(function ($dir) use ($termo, $extensoesSuportadas) {
                $marca = basename($dir);
                $marcaLower = strtolower($marca);

                $modelos = collect(Storage::files($dir))
                    ->filter(function ($file) use ($extensoesSuportadas, $termo) {
                        $extensao = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (!in_array($extensao, $extensoesSuportadas)) {
                            return false;
                        }
                        $nomeModelo = strtolower(pathinfo(basename($file), PATHINFO_FILENAME));
                        return strpos($nomeModelo, $termo) !== false;
                    })
                    ->map(function ($file) use ($marca) {
                        $nomeArquivo = basename($file);
                        $caminhoArquivo = "app/public/testpoint/$marca/$nomeArquivo";
                        $urlRelativa = asset($caminhoArquivo);
                        $urlCompleta = url($urlRelativa);

                        return [
                            'modelo' => pathinfo($nomeArquivo, PATHINFO_FILENAME),
                            'imagem_url' => $urlCompleta,
                        ];
                    });

                // Inclui a marca se ela corresponder ao termo ou tiver modelos correspondentes
                if (strpos($marcaLower, $termo) !== false || !$modelos->isEmpty()) {
                    return [$marca => $modelos];
                }
                return [];
            })
            ->filter();
    }

    return response()->json([
        'termo' => $request->input('termo', ''),
        'resultados' => $resultados
    ], 200);
}








    



    // Função para deletar uma marca (pasta e todos os modelos dentro dela)
    public function deletarMarca(Request $request)
    {
        $request->validate([
            'marca' => 'required|string',
        ]);

        $marca = Str::slug($request->input('marca'));
        $path = "testpoint/{$marca}";

        // Verifica se a pasta da marca existe
        if (!Storage::exists($path)) {
            return response()->json([
                'error' => "Marca '{$request->input('marca')}' não encontrada!"
            ], 404); // Status 404: Not Found
        }

        // Deleta a pasta da marca e todo o conteúdo dentro dela
        Storage::deleteDirectory($path);

        return response()->json([
            'message' => "Marca '{$request->input('marca')}' e todos os seus modelos foram deletados com sucesso!"
        ], 200); // Status 200: OK
    }

    // Função para editar o nome de uma marca (renomear pasta)
    public function editarMarca(Request $request)
    {
        $request->validate([
            'marca_antiga' => 'required|string',
            'marca_nova' => 'required|string',
        ]);

        $marcaAntiga = Str::slug($request->input('marca_antiga'));
        $marcaNova = Str::slug($request->input('marca_nova'));
        $pathAntigo = "testpoint/{$marcaAntiga}";
        $pathNovo = "testpoint/{$marcaNova}";

        // Verifica se a pasta da marca antiga existe
        if (!Storage::exists($pathAntigo)) {
            return response()->json([
                'error' => "Marca '{$request->input('marca_antiga')}' não encontrada!"
            ], 404); // Status 404: Not Found
        }

        // Verifica se a nova marca já existe
        if (Storage::exists($pathNovo)) {
            return response()->json([
                'error' => "Marca '{$request->input('marca_nova')}' já existe!"
            ], 409); // Status 409: Conflict
        }

        // Renomeia a pasta movendo todos os arquivos
        Storage::move($pathAntigo, $pathNovo);

        return response()->json([
            'message' => "Marca '{$request->input('marca_antiga')}' renomeada para '{$request->input('marca_nova')}' com sucesso!",
            'marca_antiga' => $marcaAntiga,
            'marca_nova' => $marcaNova
        ], 200); // Status 200: OK
    }

    // Função para deletar um modelo (apenas a imagem do modelo)
    public function deletarModelo(Request $request)
    {
        $request->validate([
            'marca' => 'required|string',
            'modelo' => 'required|string',
        ]);

        $marca = Str::slug($request->input('marca'));
        $modelo = Str::slug($request->input('modelo'));
        $path = "testpoint/{$marca}";

        // Verifica se a pasta da marca existe
        if (!Storage::exists($path)) {
            return response()->json([
                'error' => "Marca '{$request->input('marca')}' não encontrada!"
            ], 404); // Status 404: Not Found
        }

        // Procura o arquivo correspondente ao modelo
        $arquivoEncontrado = collect(Storage::files($path))
            ->first(function ($file) use ($modelo) {
                return Str::startsWith(basename($file), $modelo . '.');
            });

        if (!$arquivoEncontrado) {
            return response()->json([
                'error' => "Modelo '{$request->input('modelo')}' não encontrado na marca '{$request->input('marca')}'!"
            ], 404); // Status 404: Not Found
        }

        // Deleta o arquivo do modelo
        Storage::delete($arquivoEncontrado);

        return response()->json([
            'message' => "Modelo '{$request->input('modelo')}' deletado com sucesso da marca '{$request->input('marca')}'!"
        ], 200); // Status 200: OK
    }

    // Função para editar um modelo (trocar nome e/ou imagem)
    public function editarModelo(Request $request)
    {
        $request->validate([
            'marca' => 'required|string',
            'modelo_antigo' => 'required|string',
            'modelo_novo' => 'nullable|string',
            'imagem' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Imagem opcional, limite de 2MB
        ]);

        $marca = Str::slug($request->input('marca'));
        $modeloAntigo = Str::slug($request->input('modelo_antigo'));
        $modeloNovo = $request->has('modelo_novo') ? Str::slug($request->input('modelo_novo')) : $modeloAntigo;
        $path = "testpoint/{$marca}";

        // Verifica se a pasta da marca existe
        if (!Storage::exists($path)) {
            return response()->json([
                'error' => "Marca '{$request->input('marca')}' não encontrada!"
            ], 404); // Status 404: Not Found
        }

        // Procura o arquivo correspondente ao modelo antigo
        $arquivoEncontrado = collect(Storage::files($path))
            ->first(function ($file) use ($modeloAntigo) {
                return Str::startsWith(basename($file), $modeloAntigo . '.');
            });

        if (!$arquivoEncontrado) {
            return response()->json([
                'error' => "Modelo '{$request->input('modelo_antigo')}' não encontrado na marca '{$request->input('marca')}'!"
            ], 404); // Status 404: Not Found
        }

        $responseData = [
            'message' => "Modelo atualizado com sucesso!",
            'marca' => $marca
        ];

        // Se um novo nome foi fornecido, renomeia o arquivo
        if ($modeloNovo !== $modeloAntigo) {
            $extensao = pathinfo($arquivoEncontrado, PATHINFO_EXTENSION);
            $novoNome = "{$modeloNovo}.{$extensao}";
            $novoCaminho = $path . '/' . $novoNome;

            // Verifica se o novo nome já existe
            if (Storage::exists($novoCaminho)) {
                return response()->json([
                    'error' => "Modelo '{$request->input('modelo_novo')}' já existe na marca '{$request->input('marca')}'!"
                ], 409); // Status 409: Conflict
            }

            // Renomeia o arquivo
            Storage::move($arquivoEncontrado, $novoCaminho);
            $responseData['message'] .= " Nome atualizado para '{$request->input('modelo_novo')}'.";
            $responseData['modelo_antigo'] = $modeloAntigo;
            $responseData['modelo_novo'] = $modeloNovo;
            $arquivoEncontrado = $novoCaminho; // Atualiza para usar o novo caminho se houver imagem nova
        }

        // Se uma nova imagem foi fornecida, substitui a antiga
        if ($request->hasFile('imagem')) {
            $imagem = $request->file('imagem');
            $extensao = $imagem->getClientOriginalExtension();
            $nomeImagem = $modeloNovo . '.' . $extensao;
            $imagemPath = $path . '/' . $nomeImagem;

            // Deleta a imagem antiga (se renomeada, já está no novo caminho)
            Storage::delete($arquivoEncontrado);

            // Salva a nova imagem
            Storage::putFileAs($path, $imagem, $nomeImagem);
            $responseData['message'] .= " Imagem atualizada com sucesso.";
            $responseData['imagem'] = $nomeImagem;
        }

        return response()->json($responseData, 200); // Status 200: OK
    }
    
    
    
   public function listarUsuarios(Request $request)
{
    $busca = $request->input('busca');

    // Busca por nome se informado, senão lista todos
    $usuarios = User::when($busca, function ($query, $busca) {
            return $query->where('name', 'like', '%' . $busca . '%');
        })
        ->get();

    return view('admin.clientslist', compact('usuarios', 'busca'));
}





public function alterarPorcentagemReseller(Request $request)
{
    // Validação (retorna response automática se falhar)
    $validated = $request->validate([
        'id' => ['required', 'integer', 'exists:users,id'],
        'porcent_resseller' => ['required', 'numeric', 'min:0', 'max:100'],
    ]);

    try {
        $usuario = User::findOrFail($validated['id']);
        $usuario->porcent_resseller = $validated['porcent_resseller'];
        $usuario->save();

        // Retornar sucesso
        return response()->json([
            'message' => 'Percentual do revendedor atualizado com sucesso!',
            'user_id' => $usuario->id,
            'novo_percentual' => $usuario->porcent_resseller,
        ], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'error' => 'Usuário não encontrado!'
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Ocorreu um erro ao atualizar o percentual!'
        ], 500);
    }
}
}