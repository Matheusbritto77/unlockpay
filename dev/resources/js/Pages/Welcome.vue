<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});

const menuOpen = ref(false);

// Função para abrir o link de download em uma nova aba
const openDownload = () => {
    window.open('/download', '_blank');
};
</script>

<template>
    <Head title="UnlockPay - Gestão de Pagamentos" />

    <div class="bg-[#181848] text-white min-h-screen flex flex-col relative">
        <header class="w-full py-6 px-6 flex justify-between items-center bg-[#304878] shadow-md">
            <div class="flex items-center">
                <img src="/logo/logo.png" alt="UnlockPay Logo" class="h-12 w-auto" />
                <span class="ml-3 text-xl font-bold text-white">UnlockPay</span>
            </div>

            <!-- Menu Desktop -->
            <nav v-if="props.canLogin" class="hidden md:flex space-x-4 items-center">
                <Link :href="route('about')" class="text-white hover:underline">
                    Sobre a Gente
                </Link>
                <Link :href="route('howto')" class="text-white hover:underline">
                    Como Usar
                </Link>
                <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-white hover:underline">
                    Dashboard
                </Link>
                <template v-else>
                    <Link :href="route('login')" class="text-white hover:underline">
                        Entrar
                    </Link>
                    <Link v-if="props.canRegister" :href="route('register')" class="text-white hover:underline">
                        Criar Conta
                    </Link>
                    <a @click.prevent="openDownload" class="text-white hover:underline cursor-pointer">
                        Download
                    </a>
                </template>
            </nav>

            <!-- Botão do menu hambúrguer -->
            <button @click="menuOpen = !menuOpen" class="md:hidden text-white focus:outline-none">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                     xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
            </button>
        </header>

        <!-- Menu Mobile -->
        <nav v-if="menuOpen" class="md:hidden bg-[#304878] py-4 px-6 flex flex-col items-end space-y-4">
            <Link :href="route('about')" class="text-white hover:underline">
                Sobre a Gente
            </Link>
            <Link :href="route('howto')" class="text-white hover:underline">
                Como Usar
            </Link>
            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-white hover:underline">
                Dashboard
            </Link>
            <template v-else>
                <Link :href="route('login')" class="text-white hover:underline">
                    Entrar
                </Link>
                <Link v-if="props.canRegister" :href="route('register')" class="text-white hover:underline">
                    Criar Conta
                </Link>
                <a @click.prevent="openDownload" class="text-white hover:underline cursor-pointer">
                    Download
                </a>
            </template>
        </nav>

        <main class="flex flex-1 flex-col items-center justify-center px-6 text-center">
            <h1 class="text-4xl font-extrabold text-white">Bem-vindo ao UnlockPay</h1>
            <p class="mt-4 text-lg text-gray-300 max-w-2xl">
                O UnlockPay é um sistema completo para gestão de pagamentos, integração com Mercado Pago, gerenciamento
                de clientes e histórico de transações. Com nossa API do WhatsApp, você pode gerar cobranças
                automaticamente e acompanhar seus pagamentos com facilidade.
            </p>
        </main>

        <footer class="py-6 text-center text-sm text-gray-300">
            UnlockPay - Desenvolvido por britto_Dev &copy; 2025
        </footer>
    </div>
</template>
