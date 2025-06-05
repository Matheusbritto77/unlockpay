<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const resellerInfo = ref({});
const clientesIndicados = ref({
  total_clientes_indicados: 0,
  clientes_com_plano_pago: 0
});
const notifications = reactive({
  url: false,
  code: false
});

onMounted(async () => {
  try {
    // Carrega informações do revendedor
    const resellerResponse = await axios.get('/reseller-info');
    resellerInfo.value = resellerResponse.data;

    // Carrega informações de clientes indicados
    const clientesResponse = await axios.get('/clientes-indicados');
    if (clientesResponse.data.success) {
      clientesIndicados.value.total_clientes_indicados = clientesResponse.data.total_clientes_indicados;
      clientesIndicados.value.clientes_com_plano_pago = clientesResponse.data.clientes_com_plano_pago;
    }
  } catch (error) {
    console.error('Erro ao carregar informações:', error);
  }
});

const copiarUrl = () => {
  if (!resellerInfo.value.url_indicacao) return;
  navigator.clipboard.writeText(resellerInfo.value.url_indicacao)
    .then(() => {
      notifications.url = true;
      setTimeout(() => {
        notifications.url = false;
      }, 2000);
    })
    .catch(err => {
      console.error('Erro ao copiar URL:', err);
    });
};

const copiarCodigo = () => {
  if (!resellerInfo.value.codigo_revendedor) return;
  navigator.clipboard.writeText(resellerInfo.value.codigo_revendedor)
    .then(() => {
      notifications.code = true;
      setTimeout(() => {
        notifications.code = false;
      }, 2000);
    })
    .catch(err => {
      console.error('Erro ao copiar código:', err);
    });
};

const sacarCredito = () => {
  axios.get('/saque')
    .then(response => {
      alert(response.data.message);
    })
    .catch(error => {
      if (error.response) {
        alert(error.response.data.error || 'Erro ao sacar crédito');
      } else {
        console.error(error);
        alert('Erro inesperado');
      }
    });
};
</script>

<template>
  <AppLayout title="Painel de Revendedor">
    <template #header>
      <h2 class="font-semibold text-2xl text-gray-100 leading-tight bg-gradient-to-r from-blue-400 to-blue-600 bg-clip-text text-transparent">
        Painel de Revendedor
      </h2>
    </template>

    <div class="py-12 bg-gray-100 min-h-screen">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Card Principal de Informações -->
          <div class="bg-white bg-opacity-80 backdrop-blur-lg overflow-hidden shadow-2xl rounded-xl p-6 border border-gray-200 transition transform hover:scale-105 hover:shadow-blue-500/50 hover:border-blue-400">
            <h2 class="font-bold text-xl mb-4 text-blue-500 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Informações do Revendedor
            </h2>
            <div class="space-y-3 text-gray-700">
              <p class="flex items-center">
                <span class="w-40 text-blue-400 font-semibold">Nome:</span> 
                <span class="text-gray-800">{{ resellerInfo.name || '-' }}</span>
              </p>
              <p class="flex items-center">
                <span class="w-40 text-blue-400 font-semibold">Email:</span> 
                <span class="text-gray-800">{{ resellerInfo.email || '-' }}</span>
              </p>
              <div class="flex items-center relative">
                <span class="w-40 text-blue-400 font-semibold">Código:</span> 
                <span class="text-gray-800">{{ resellerInfo.codigo_revendedor || '-' }}</span>
                <button 
                  @click="copiarCodigo" 
                  class="ml-3 bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-3 rounded-lg shadow-md transition flex items-center"
                  :disabled="!resellerInfo.codigo_revendedor"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  Copiar
                </button>
                <!-- Notificação de cópia -->
                <div v-if="notifications.code" class="absolute right-0 top-full mt-2 bg-green-100 text-green-700 px-3 py-1 rounded-md text-sm shadow-md">
                  Código copiado!
                </div>
              </div>
            </div>
          </div>

          <!-- Card de Comissão e Crédito -->
          <div class="bg-white bg-opacity-80 backdrop-blur-lg overflow-hidden shadow-2xl rounded-xl p-6 border border-gray-200 transition transform hover:scale-105 hover:shadow-blue-500/50 hover:border-blue-400">
            <h2 class="font-bold text-xl mb-4 text-blue-500 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Detalhes Financeiros
            </h2>
            <div class="space-y-3 text-gray-700">
              <p class="flex items-center">
                <span class="w-40 text-blue-400 font-semibold">Comissão:</span> 
                <span class="text-gray-800">{{ resellerInfo.porcent_resseller ? `${resellerInfo.porcent_resseller}%` : '-' }}</span>
              </p>
              <div>
                <span class="block text-sm text-gray-600 mb-1 font-medium">
                  Para solicitar um saque é necessário ter no mínimo <b>R$ 100,00</b> de saldo.
                </span>
                <div class="flex items-center">
                  <span class="w-40 text-blue-400 font-semibold">Crédito Total:</span> 
                  <span class="text-gray-800 mr-3">{{ resellerInfo.credito || '0' }}</span>
                  <button 
                    class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-3 rounded-lg shadow-md transition"
                    @click="sacarCredito"
                    :disabled="!resellerInfo.credito || resellerInfo.credito < 100"
                    title="O saque só é permitido acima de R$ 100,00"
                  >
                    Sacar
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Card de URL de Indicação -->
          <div class="bg-white bg-opacity-80 backdrop-blur-lg overflow-hidden shadow-2xl rounded-xl p-6 border border-gray-200 transition transform hover:scale-105 hover:shadow-blue-500/50 hover:border-blue-400 md:col-span-2">
            <h2 class="font-bold text-xl mb-4 text-blue-500 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
              </svg>
              URL de Indicação
            </h2>
            <div class="flex flex-col md:flex-row items-center text-gray-700 space-y-3 md:space-y-0 md:space-x-3 relative">
              <span class="w-full md:w-auto text-blue-400 font-semibold">Link:</span>
              <div class="w-full md:flex-1 bg-gray-200 rounded-lg p-2 text-gray-800 truncate">{{ resellerInfo.url_indicacao ? resellerInfo.url_indicacao.replace(':///', '://').replace('//', '/') : 'Carregando...' }}</div>
              <button 
                @click="copiarUrl" 
                class="ml-3 bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-3 rounded-lg shadow-md transition flex items-center outra-classe"
                :disabled="!resellerInfo.url_indicacao"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Copiar
              </button>
              <!-- Notificação de cópia -->
              <div v-if="notifications.url" class="absolute right-0 bottom-full mb-2 bg-green-100 text-green-700 px-3 py-1 rounded-md text-sm shadow-md">
                URL copiada!
              </div>
            </div>
          </div>

          <!-- Novo Card de Clientes Indicados -->
          <div class="bg-white bg-opacity-80 backdrop-blur-lg overflow-hidden shadow-2xl rounded-xl p-6 border border-gray-200 transition transform hover:scale-105 hover:shadow-blue-500/50 hover:border-blue-400 md:col-span-2">
            <h2 class="font-bold text-xl mb-4 text-blue-500 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v2h5v-2zm0 0a3 3 0 005.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              Clientes Indicados
            </h2>
            <div class="space-y-3 text-gray-700">
              <p class="flex items-center">
                <span class="w-40 text-blue-400 font-semibold">Total Indicados:</span> 
                <span class="text-gray-800">{{ clientesIndicados.total_clientes_indicados }}</span>
              </p>
              <p class="flex items-center">
                <span class="w-40 text-blue-400 font-semibold">Com Plano Pago:</span> 
                <span class="text-gray-800">{{ clientesIndicados.clientes_com_plano_pago }}</span>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* seus estilos originais... */
.transform {
  transition: all 0.3s ease;
}
.transform:hover {
  box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.5);
}
button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
div[v-if="notifications.url"], 
div[v-if="notifications.code"] {
  animation: fadeInOut 2s ease;
}
@keyframes fadeInOut {
  0% { opacity: 0; transform: translateY(-10px); }
  10% { opacity: 1; transform: translateY(0); }
  90% { opacity: 1; transform: translateY(0); }
  100% { opacity: 0; transform: translateY(-10px); }
}
</style>