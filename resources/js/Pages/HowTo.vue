<template>
    <div class="p-8 text-white bg-[#181848] min-h-screen">
        <!-- Botão de voltar -->
        <Link href="/" class="inline-flex items-center text-white hover:text-blue-400 mb-6">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
            </svg>
            Voltar
        </Link>

        <h1 class="text-3xl font-bold mb-4">Como Usar o UnlockPay</h1>
        <p class="text-lg text-gray-300 mb-6">
            Aqui você encontrará instruções para usar todas as funcionalidades do UnlockPay, desde integrações até controle via WhatsApp.
        </p>

        <!-- Requisitos -->
        <section class="mb-8">
            <h2 class="text-2xl font-semibold mb-2">🔧 Requisitos para usar o sistema</h2>
            <ul class="list-disc list-inside text-gray-300">
                <li>Conta ativa no <strong>Mercado Pago</strong>.</li>
                <li>Chave aleatória PIX cadastrada no Mercado Pago.</li>
                <li>Acesso ao <strong>WhatsApp</strong> no celular com internet ativa.</li>
            </ul>
        </section>

        <!-- Módulo de Instâncias WhatsApp -->
        <section class="mb-8">
            <h2 class="text-2xl font-semibold mb-2">📲 Módulo: Instância WhatsApp</h2>
            <ol class="list-decimal list-inside text-gray-300 space-y-1">
                <li>Criar uma nova instância e dar um nome (sem espaços).</li>
                <li>Aguardar 30 segundos e escanear o QR Code com o WhatsApp.</li>
                <li>Após escanear, clicar em <strong>Status</strong> para verificar a conexão.</li>
                <li>Se o campo de status aparecer em branco, significa que ainda não está conectado.</li>
                <li>Caso a instância não conecte após já ter funcionado antes, clique em <strong>Restart API</strong> para reiniciar a API e tentar reconectar.</li>
                <li>Mantenha o WhatsApp aberto no celular durante o processo de verificação e conexão.</li>
            </ol>

            <div class="mt-4 text-gray-300">
                <p><strong>Botões disponíveis:</strong></p>
                <ul class="list-disc list-inside ml-4">
                    <li><strong>Reset:</strong> Reinicia a instância sem apagar os dados.</li>
                    <li><strong>Delete:</strong> Deleta a instância permanentemente.</li>
                </ul>
            </div>
        </section>

        <!-- Módulo Mercado Pago -->
        <section>
            <h2 class="text-2xl font-semibold mb-2">💰 Módulo: Integração Mercado Pago</h2>
            <ol class="list-decimal list-inside text-gray-300 space-y-1">
                <li>Acesse o site <a href="https://www.mercadopago.com.br/developers" target="_blank" class="text-blue-400 underline">Mercado Pago Developers</a>.</li>
                <li>Crie uma nova integração com permissão de gerar pagamentos via PIX.</li>
                <li>Copie a <strong>Access Token de produção</strong>.</li>
                <li>No sistema UnlockPay, clique no botão <strong>"Token Mercado Pago"</strong> e cole sua chave para conectar.</li>
            </ol>
        </section>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>
