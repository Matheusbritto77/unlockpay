<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const estatisticas = ref({
    categorias_pagas: [],
    total_diario_pago: 0,
    total_custo_diario: 0,
    total_lucro_diario: 0
});

const carregarEstatisticas = async () => {
    try {
        const response = await axios.get('/estatisticas-pagamentos');
        estatisticas.value = response.data;
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
    }
};

onMounted(carregarEstatisticas);
</script>

<template>
    <div class="p-6 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card Total Pago do Dia -->
                <div class="bg-green-100 border-l-4 border-green-500 rounded p-4">
                    <h2 class="text-xl font-semibold text-green-600">Total Pago do Dia</h2>
                    <p class="text-3xl font-bold text-green-700">R$ {{ estatisticas.total_diario_pago.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</p>
                </div>

                <!-- Card Total Custo do Dia -->
                <div class="bg-orange-100 border-l-4 border-orange-500 rounded p-4">
                    <h2 class="text-xl font-semibold text-orange-600">Total Custo do Dia</h2>
                    <p class="text-3xl font-bold text-orange-700">R$ {{ estatisticas.total_custo_diario.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</p>
                </div>

                <!-- Card Total Lucro do Dia -->
                <div class="bg-blue-100 border-l-4 border-blue-500 rounded p-4">
                    <h2 class="text-xl font-semibold text-blue-600">Total Lucro do Dia</h2>
                    <p class="text-3xl font-bold text-blue-700">R$ {{ estatisticas.total_lucro_diario.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</p>
                </div>
            </div>

            <!-- Gráfico de Categorias Pagas -->
            <div class="mt-6 bg-white shadow-md rounded-lg p-4">
                <h2 class="text-xl font-semibold text-gray-700">Top Categorias Pagas</h2>
                <ul class="mt-4 space-y-2">
                    <li v-for="(categoria, index) in estatisticas.categorias_pagas" :key="index" class="flex justify-between">
                        <span class="text-gray-700">{{ categoria.categoria }}</span>
                        <span class="text-gray-700">{{ categoria.total }} vezes</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Estilos adicionais */
</style>