<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import FormSection from '@/Components/FormSection.vue';

const plano = ref(null);
const carregandoPlano = ref(false);
const erroPlano = ref('');

onMounted(async () => {
    carregandoPlano.value = true;
    try {
        const response = await axios.get('/meu-plano');
        plano.value = response.data;
    } catch (e) {
        erroPlano.value = 'Não foi possível carregar as informações do plano.';
    } finally {
        carregandoPlano.value = false;
    }
});
</script>

<template>
    <FormSection>
        <template #title>
            Seu Plano
        </template>

        <template #description>
            Visualize detalhes sobre seu plano vigente e validade de acordo com sua assinatura atual.
        </template>

        <template #form>
            <div class="col-span-6">
                <div v-if="carregandoPlano" class="text-gray-500 py-2">
                    Carregando informações do plano...
                </div>
                <div v-else-if="erroPlano" class="text-red-500 py-2">
                    {{ erroPlano }}
                </div>
                <div v-else-if="plano && plano.plan" class="space-y-2">
                    <div>
                        <span class="font-medium text-gray-700">Nome:</span>
                        <span class="ml-1 text-gray-900">{{ plano.plan.nome }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Descrição:</span>
                        <span class="ml-1 text-gray-900">{{ plano.plan.descricao }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Preço:</span>
                        <span class="ml-1 text-gray-900">
                            <span v-if="Number(plano.plan.preco) > 0">
                                R$ {{ Number(plano.plan.preco).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}
                            </span>
                            <span v-else>Grátis</span>
                        </span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Duração:</span>
                        <span class="ml-1 text-gray-900">{{ plano.plan.duracao_dias }} dia(s)</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Tipo:</span>
                        <span class="ml-1 text-gray-900">
                            <span v-if="plano.plan.tipo === 'teste'">Teste</span>
                            <span v-else-if="plano.plan.tipo === 'pago'">Pago</span>
                            <span v-else-if="plano.plan.tipo === 'livre'">Livre</span>
                            <span v-else>{{ plano.plan.tipo }}</span>
                        </span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Início:</span>
                        <span class="ml-1 text-gray-900">{{ plano.data_inicio }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Fim:</span>
                        <span class="ml-1 text-gray-900">{{ plano.data_fim }}</span>
                    </div>
                </div>
                <div v-else class="text-gray-500 py-2">
                    Nenhuma informação de plano disponível.
                </div>
            </div>
        </template>
    </FormSection>
</template>