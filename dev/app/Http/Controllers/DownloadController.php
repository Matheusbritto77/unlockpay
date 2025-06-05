<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;

class DownloadController extends Controller
{
    /**
     * Função para forçar o download do arquivo unlockpay.zip
     */
    public function download()
    {
        // Defina o caminho do arquivo
        $filePath = public_path('files/unlockpay.zip');

        // Verifique se o arquivo existe
        if (File::exists($filePath)) {
            return Response::download($filePath);
        } else {
            // Retorna um HTML com um modal embutido
            return response("
                <html>
                    <head>
                        <title>Erro - Download</title>
                        <style>
                            body { font-family: Arial, sans-serif; background-color: #181848; color: white; text-align: center; padding: 50px; }
                            .modal { background: rgba(0, 0, 0, 0.8); padding: 20px; border-radius: 8px; display: inline-block; }
                            .button { padding: 10px 20px; background: red; color: white; border: none; cursor: pointer; margin-top: 10px; }
                        </style>
                    </head>
                    <body>
                        <div class='modal'>
                            <h2>Erro ao baixar o arquivo</h2>
                            <p>O arquivo não foi encontrado no servidor.</p>
                            <button class='button' onclick='window.history.back()'>Voltar</button>
                        </div>
                    </body>
                </html>
            ", 404);
        }
    }
}
