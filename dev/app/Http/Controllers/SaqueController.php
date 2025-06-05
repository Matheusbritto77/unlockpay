<?php


// app/Http/Controllers/SaqueController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use App\Models\Instancia;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SaqueController extends Controller
{
    public function sacar()
    {
        $user = Auth::user();

        // Verifica se o usuário tem saldo
        if ($user->credito <= 0) {
            return response()->json(['error' => 'Saldo insuficiente'], 422);
        }

        // Só permite saque de no mínimo 100 reais
        if ($user->credito < 100) {
            return response()->json(['error' => 'O valor mínimo para saque é R$ 100,00'], 422);
        }

        $instancia = Instancia::where('user_id', 1)->first();

        if (!$instancia) {
            return response()->json(['error' => 'Instância não encontrada'], 404);
        }

        $instanciaName = $instancia->name;

        // Prepara a mensagem para o WhatsApp
        $apiUrl = "https://dapleserver.com/client/sendMessage/$instanciaName";
        $apiToken = "asdfghjksjhggbnmfdfghjhgf"; // Substitua pelo token real da API do WhatsApp
        $chatId = "120363397854570559@g.us";
        $valor = $user->credito;
        $mensagemTexto = "Olá, saque solicitado para a chave Pix **{$user->chave_pix}** no valor de *R$ " . number_format($valor, 2, ',', '.') . "* foi gerada. Copie e cole o código abaixo:";
        $mensagemTextoPayload = [
            'chatId' => $chatId,
            'contentType' => 'string',
            'content' => $mensagemTexto,
        ];

        // Envia a primeira mensagem
        $response = Http::withHeaders([
            'x-api-key' => $apiToken,
            'Content-Type' => 'application/json'
        ])->post($apiUrl, $mensagemTextoPayload);

        // Verifica se a requisição foi bem-sucedida
        if ($response->successful()) {
            // Envia a segunda mensagem com a chave Pix isolada
            $mensagemTextoPayload['content'] = $user->chave_pix;
            $response = Http::withHeaders([
                'x-api-key' => $apiToken,
                'Content-Type' => 'application/json'
            ])->post($apiUrl, $mensagemTextoPayload);

            // Verifica se a segunda requisição foi bem-sucedida
            if ($response->successful()) {
                // Zera o crédito do usuário
                $user->credito = 0;
                $user->save();

                return response()->json(['message' => 'Saque solicitado com sucesso'], 200);
            } else {
                return response()->json(['error' => 'Erro ao enviar a segunda mensagem'], 500);
            }
        } else {
            return response()->json(['error' => 'Erro ao enviar a mensagem'], 500);
        }
    }
}