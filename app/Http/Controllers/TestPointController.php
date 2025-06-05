<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Support\Str;

class TestPointController extends Controller
{
   


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

    // Retorna os dados no formato JSON
    return response()->json([
        'status' => 'success',
        'data' => [
            'marcas_modelos' => $marcas
        ]
    ]);
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




   
}