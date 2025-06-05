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

        <h1 class="text-3xl font-bold mb-4">Sobre a Gente</h1>

        <p class="text-lg text-gray-300 mb-4">
            O UnlockPay é uma plataforma desenvolvida para facilitar a gestão de cobranças e pagamentos.
            Nossa missão é simplificar processos e integrar tecnologia com eficiência.
        </p>

        <p class="text-lg text-gray-300">
            A <strong>UnlockTeam</strong> é focada em trazer soluções de software para o mercado de unlocker e software GSM,
            além de atender empresas do ramo de manutenção de telefones em todo o mundo.
        </p>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>
