<script setup>
import { ref } from 'vue';

// --------------------
// Planos de Assinatura
// --------------------
const planos = [
  
  {
    id: 2,
    nome: 'Plano de Teste',
    descricao: 'Teste gratuito por 15 dias.',
    preco: 0.00,
    duracao_dias: 15,
    tipo: 'teste',
    ativo: 1,
    icone: 'calendar-week',
    destaque: false,
    recursos: [
      'Acesso limitado aos recursos',
      'Suporte básico',
      'Sem anúncios',
    ],
  },
  {
    id: 3,
    nome: 'Plano Mensal',
    descricao: 'Acesso premium por 1 mês.',
    preco: 49.90,
    duracao_dias: 30,
    tipo: 'pago',
    ativo: 1,
    icone: 'calendar-month',
    destaque: false,
    recursos: [
      'Acesso a todos os recursos',
      'Suporte prioritário',
      'Sem anúncios',
    ],
  },
  {
    id: 4,
    nome: 'Plano 6 Meses',
    descricao: 'Acesso premium por 6 meses.',
    preco: 269.90,
    duracao_dias: 180,
    tipo: 'pago',
    ativo: 1,
    icone: 'calendar-range',
    destaque: true,
    recursos: [
      'Acesso a todos os recursos',
      'Suporte prioritário',
      'Sem anúncios',
      'Conteúdo exclusivo',
      'Downloads ilimitados',
    ],
  },
  {
    id: 5,
    nome: 'Plano Anual',
    descricao: 'Acesso premium por 1 ano.',
    preco: 499.00,
    duracao_dias: 365,
    tipo: 'pago',
    ativo: 1,
    icone: 'calendar-star',
    destaque: false,
    recursos: [
      'Acesso a todos os recursos',
      'Suporte prioritário',
      'Sem anúncios',
      'Conteúdo exclusivo',
      'Downloads ilimitados',
      'Acesso antecipado a novidades',
    ],
  },
];

// --------------------
// Utilitários
// --------------------
const formatarPreco = (valor) => {
  return valor.toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  });
};

const calcularEconomia = (preco, dias) => {
  const precoMensal = planos.find(p => p.nome === 'Plano Mensal')?.preco || 49.90;
  const meses = dias / 30;
  const precoMensalEquivalente = preco / meses;
  return Math.round((1 - precoMensalEquivalente / precoMensal) * 100);
};

const redirecionarParaRegistro = () => {
  window.location.href = '/registro';
};

const voltar = () => {
  window.location.href = '/';
};
</script>

<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-purple-50 py-16 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      
      <!-- Botão de Voltar -->
      <div class="mb-8">
        <button
          @click="voltar"
          class="flex items-center text-purple-600 hover:text-purple-800 font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500"
        >
          <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
          Voltar
        </button>
      </div>

      <!-- Cabeçalho -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-4">Escolha seu Plano Premium</h1>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
          Selecione a opção que melhor atende às suas necessidades e comece a aproveitar todos os benefícios.
        </p>
      </div>

      <!-- Planos -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div
          v-for="plano in planos"
          :key="plano.id"
          class="bg-white rounded-2xl shadow-xl overflow-hidden transform transition-all duration-300 hover:scale-[1.02]"
          :class="plano.destaque ? 'ring-4 ring-purple-500 ring-opacity-50' : ''"
        >
          <div v-if="plano.destaque" class="bg-purple-600 text-white text-center py-2 font-medium">
            Melhor valor
          </div>

          <div class="p-8 flex flex-col h-full">
            <div class="flex-1">
              <div class="flex items-center mb-4">
                <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center mr-4">
                  <svg class="h-6 w-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z"/>
                  </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">{{ plano.nome }}</h2>
              </div>

              <div class="mb-6">
                <p class="text-4xl font-extrabold text-purple-700">{{ formatarPreco(plano.preco) }}</p>
                <p class="text-gray-500 mt-1">
                  <span v-if="plano.duracao_dias > 30">
                    ({{ formatarPreco(plano.preco / (plano.duracao_dias / 30)) }} por mês)
                  </span>
                  <span v-else-if="plano.duracao_dias === 30">Por mês</span>
                  <span v-else-if="plano.duracao_dias === 15">Por quinzena</span>
                  <span v-else>Gratuito</span>
                </p>
              </div>

              <div
                v-if="plano.duracao_dias > 30"
                class="bg-green-50 text-green-700 px-4 py-2 rounded-lg text-sm font-medium mb-6"
              >
                Economize {{ calcularEconomia(plano.preco, plano.duracao_dias) }}% comparado ao plano mensal
              </div>

              <p class="text-gray-600 mb-6">{{ plano.descricao }}</p>

              <ul class="space-y-3 mb-0">
                <li v-for="(recurso, index) in plano.recursos" :key="index" class="flex items-center text-gray-600">
                  <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  <span>{{ recurso }}</span>
                </li>
              </ul>
            </div>

            <button
              @click="redirecionarParaRegistro"
              class="w-full py-2 px-6 rounded-lg font-semibold text-white transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mt-6 bg-green-500 hover:bg-green-600"
            >
              Se Inscrever
            </button>
            <br>
          </div>
        </div>
      </div>

      <!-- Programa de Indicação -->
      <div class="mt-16 bg-white rounded-2xl shadow-md p-8">
        <h2 class="text-3xl font-extrabold text-purple-700 mb-4">Indique e Ganhe</h2>
        <p class="text-gray-700 text-lg mb-4">
          Compartilhe seu <strong>link personalizado</strong> ou <strong>código de indicação</strong> com amigos e ganhe comissão em cima das assinaturas mensais realizadas por eles!
        </p>
        <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
          <li>Você recebe comissão para cada indicação válida.</li>
          <li>Quanto mais você indica, maior sua comissão!</li>
          <li>Comissões podem chegar até <strong>65%</strong> por assinatura mensal.</li>
        </ul>
        <p class="text-sm text-gray-500">
          Exemplo de link: <code class="bg-gray-100 px-2 py-1 rounded">https://exemplo.com/?ref=SEU_CODIGO</code>
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Estilo básico para o layout, com Tailwind aplicado diretamente */
</style>
