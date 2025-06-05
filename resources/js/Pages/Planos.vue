<script setup>
import { ref, onUnmounted } from 'vue';
import axios from 'axios';

// --------------------
// Planos de Assinatura
// --------------------
const planos = [
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
      'Sem anúncios'
    ]
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
      'Downloads ilimitados'
    ]
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
      'Acesso antecipado a novidades'
    ]
  },
];

// --------------------
// Estados
// --------------------
const modalAberto = ref(false);
const planoSelecionado = ref(null);
const loading = ref(false);
const pagamentoReference = ref(null);
const pollingId = ref(null);
const pagamentoSucesso = ref(false);

// --------------------
// Utilitários
// --------------------
const formatarPreco = (valor) => {
  return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};
const calcularEconomia = (preco, dias) => {
  const precoMensal = planos[0].preco;
  const meses = dias / 30;
  const precoMensalEquivalente = preco / meses;
  return Math.round((1 - precoMensalEquivalente / precoMensal) * 100);
};
const abrirModalInscricao = (plano) => {
  planoSelecionado.value = plano;
  modalAberto.value = true;
  document.body.classList.add('overflow-hidden');
};
const fecharModal = () => {
  modalAberto.value = false;
  document.body.classList.remove('overflow-hidden');
  limparPolling();
};

function limparPolling() {
  if (pollingId.value) {
    clearInterval(pollingId.value);
    pollingId.value = null;
  }
}
onUnmounted(limparPolling);

// ---------------------------------------------
// Exibir QR code e fazer polling do pagamento
// ---------------------------------------------
function exibirQrCode(data) {
  pagamentoReference.value = data.reference;
  const modal = document.createElement('div');
  modal.style.position = 'fixed';
  modal.style.left = 0;
  modal.style.top = 0;
  modal.style.width = '100vw';
  modal.style.height = '100vh';
  modal.style.background = 'rgba(0,0,0,0.5)';
  modal.style.display = 'flex';
  modal.style.justifyContent = 'center';
  modal.style.alignItems = 'center';
  modal.style.zIndex = 9999;
  modal.id = 'qr-modal-custom';
  modal.innerHTML = `
    <div style="background:#fff;padding:20px;border-radius:8px;max-width:95vw;text-align:center">
      <h3>Escaneie o QR Code PIX</h3>
      <img src="data:image/png;base64,${data.qr_code_base64}" style="width:270px;height:270px" /><br/>
      <h4>Copia e Cola:</h4>
      <input type="text" value="${data.qr_code}" style="width:90%" id="copiacola" readonly />
      <button id="copiarQr">Copiar</button>
      <br><br>
      <button id="fecharQr">Fechar</button>
    </div>
  `;
  document.body.appendChild(modal);
  document.getElementById('fecharQr').onclick = () => {
    document.body.removeChild(modal);
    limparPolling();
  };
  document.getElementById('copiarQr').onclick = () => {
    const input = document.getElementById('copiacola');
    input.select();
    document.execCommand('copy');
    alert('Copia e cola copiado!');
  };
}

const inscreverPlano = async () => {
  if (!planoSelecionado.value) return;
  loading.value = true;
  try {
    const response = await axios.post(`/pagamento/qrcode/${planoSelecionado.value.id}`);
    exibirQrCode(response.data);
    pagamentoReference.value = response.data.reference;
    limparPolling();
    console.log('Iniciando polling de status do pagamento com reference:', pagamentoReference.value);
    pollingId.value = setInterval(() => {
      if (pagamentoReference.value) {
        console.log('Chamando verificarStatusPagamento com:', pagamentoReference.value);
        verificarStatusPagamento(pagamentoReference.value);
      } else {
        console.log('pagamentoReference não definido ainda!');
      }
    }, 5000);
  } catch (e) {
    alert('Erro: ' + (e.response?.data?.message || e.message || 'Falha ao processar inscrição.'));
  } finally {
    loading.value = false;
  }
};



async function verificarStatusPagamento(reference) {
  console.log('Executando verificarStatusPagamento/reference:', reference);
  try {
    const response = await axios.get(`/pagamento/status/${reference}`);
    console.log('Busca de status:', response.data); // <-- LOG NO CONSOLE
    // Se status 0 (sucesso no pagamento)
    if (response.data.status == 0) {
      limparPolling();
      pagamentoReference.value = null; // importante: evita polling continuando
      pagamentoSucesso.value = true;
      document.body.classList.remove('overflow-hidden');
      // FECHA O QR CODE, SE ESTIVER ABERTO
      const modal = document.getElementById('qr-modal-custom');
      if (modal) document.body.removeChild(modal);
      setTimeout(() => {
        window.location.href = '/dashboard';
      }, 5000);
    }
  } catch (e) {
    console.log('Erro ao buscar status:', e.message);
  }
}
</script>


 
  


<template>
    <meta name="csrf-token" content="{{ csrf_token() }}">
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-purple-50 py-16 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
      <!-- Cabeçalho da seção -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-4">Escolha seu Plano Premium</h1>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
          Selecione a opção que melhor atende às suas necessidades e comece a aproveitar todos os benefícios.
        </p>
      </div>
      


      
     <!-- Overlay de loading absoluto e com z-index altíssimo -->
<div
  v-if="loading"
  style="position: fixed; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); z-index: 99999; display: flex; align-items: center; justify-content: center;"
>
  <div style="background: white; padding: 2.5rem 2rem; border-radius: 1rem; box-shadow: 0 8px 24px rgba(0,0,0,0.18); text-align: center">
    <svg class="mx-auto mb-4" width="64" height="64" viewBox="0 0 44 44" stroke="#8B5CF6">
      <g fill="none" fill-rule="evenodd" stroke-width="4">
        <circle cx="22" cy="22" r="18" stroke-opacity="0.2"/>
        <path d="M40 22c0-9.94-8.06-18-18-18">
          <animateTransform
            attributeName="transform"
            type="rotate"
            from="0 22 22"
            to="360 22 22"
            dur="1s"
            repeatCount="indefinite"
          />
        </path>
      </g>
    </svg>
    <span class="text-purple-700 text-lg font-semibold block">Gerando QR Code, aguarde...</span>
  </div>
</div>

    <!-- Overlay de mensagem de sucesso absoluta -->
    
    <div v-if="pagamentoSucesso"
      style="position:fixed;z-index:100000000;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);display:flex;align-items:center;justify-content:center">
      <div style="background: #fff; color: #38a169; font-size: 2rem; font-weight: bold; padding: 3rem 2.5rem; border-radius: 1rem; box-shadow:0 8px 24px rgba(0,0,0,0.25); text-align:center;">
        <svg class="inline mr-4 -mt-1" style="vertical-align:middle;" width="40" height="40" fill="none" stroke="#38a169" stroke-width="4" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke-opacity="0.15"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 12l2 2 4-4"/>
        </svg>
        Pagamento recebido com sucesso!
        <div style="font-size:1rem;color:#555;margin-top:1rem">Redirecionando para o dashboard...</div>
      </div>
    </div>

    
     <!-- Grid de planos -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
  <div 
    v-for="plano in planos" 
    :key="plano.id" 
    class="bg-white rounded-2xl shadow-xl overflow-hidden transform transition-all duration-300 hover:scale-105"
    :class="plano.destaque ? 'ring-4 ring-purple-500 ring-opacity-50' : ''"
  >
    <!-- Tag de melhor valor (apenas para o plano destacado) -->
    <div v-if="plano.destaque" class="bg-purple-600 text-white text-center py-2 font-medium">
      Melhor valor
    </div>
    
    <!-- Conteúdo do cartão de plano -->
    <div class="p-8 flex flex-col h-full">
      <div class="flex-1">
        <!-- Cabeçalho do plano -->
        <div class="flex items-center mb-4">
          <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center mr-4">
            <svg class="h-6 w-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
              <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 0 0 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z" />
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-gray-800">{{ plano.nome }}</h2>
        </div>
        
        <!-- Preço -->
        <div class="mb-6">
          <p class="text-4xl font-extrabold text-purple-700">
            {{ formatarPreco(plano.preco) }}
          </p>
          <p class="text-gray-500 mt-1">
            <span v-if="plano.duracao_dias > 30">
              ({{ formatarPreco(plano.preco / (plano.duracao_dias / 30)) }} por mês)
            </span>
            <span v-else>Por mês</span>
          </p>
        </div>
        
        <!-- Economia (para planos não mensais) -->
        <div v-if="plano.duracao_dias > 30" 
            class="bg-green-50 text-green-700 px-4 py-2 rounded-lg text-sm font-medium mb-6">
          Economize {{ calcularEconomia(plano.preco, plano.duracao_dias) }}% comparado ao plano mensal
        </div>
        
        <!-- Descrição -->
        <p class="text-gray-600 mb-6">{{ plano.descricao }}</p>
        
        <!-- Recursos -->
        <ul class="space-y-3 mb-0">
          <li v-for="(recurso, index) in plano.recursos" :key="index" class="flex items-center text-gray-600">
            <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ recurso }}</span>
          </li>
        </ul>
      </div>
     
      <!-- Botão de inscrição - subindo-o mais para cima -->
      <button
        @click="abrirModalInscricao(plano)"
        class="w-full py-2 px-6 rounded-lg font-semibold text-white transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mt-6"
        :class="plano.destaque 
          ? 'bg-green-500 hover:bg-green-600' 
          : 'bg-green-500 hover:bg-green-600'"
      >
        Assinar agora
      </button>
      <br>
    </div>
  </div>
</div>
</div>
    <!-- Modal de inscrição -->
    <div v-if="modalAberto" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div 
        class="bg-white rounded-lg max-w-md w-full shadow-2xl transform transition-all"
        @click.stop
      >
        <!-- Cabeçalho do modal -->
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
          <h3 class="text-xl font-semibold text-gray-900">Confirmar assinatura</h3>
          <button 
            @click="fecharModal"
            class="text-gray-400 hover:text-gray-500 focus:outline-none"
          >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <!-- Conteúdo do modal -->
        <div class="px-6 py-4">
          <div v-if="planoSelecionado" class="mb-6">
            <p class="text-gray-700 mb-4">Você está prestes a assinar o:</p>
            
            <div class="bg-purple-50 rounded-lg p-4 mb-4">
              <h4 class="text-xl font-bold text-purple-800 mb-2">{{ planoSelecionado.nome }}</h4>
              <p class="text-gray-600 mb-2">{{ planoSelecionado.descricao }}</p>
              <p class="text-2xl font-bold text-purple-700">{{ formatarPreco(planoSelecionado.preco) }}</p>
            </div>
            
            <p class="text-sm text-gray-500">
              Ao confirmar, você concorda com nossos termos de serviço e política de privacidade.
              Sua assinatura começará imediatamente e será renovada automaticamente.
            </p>
          </div>
          
          <!-- Formulário de pagamento simulado -->
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-2">
              Método de pagamento
            </label>
            <div class="flex gap-3 mb-4">
             
                <button
  @click="inscreverPlano"
  type="button"
  class="border border-gray-300 rounded-md px-4 py-2 flex items-center justify-center hover:bg-gray-50 focus:ring-2 focus:ring-purple-500"
>
  <svg class="h-6 w-6 text-green-600 mr-2" viewBox="0 0 24 24" fill="currentColor">
    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.31-8.86c-1.77-.45-2.34-.94-2.34-1.67 0-.84.79-1.43 2.1-1.43 1.38 0 1.9.66 1.94 1.64h1.71c-.05-1.34-.87-2.57-2.49-2.97V5H10.9v1.69c-1.51.32-2.72 1.3-2.72 2.81 0 1.79 1.49 2.69 3.66 3.21 1.95.46 2.34 1.15 2.34 1.87 0 .53-.39 1.39-2.1 1.39-1.6 0-2.23-.72-2.32-1.64H8.04c.1 1.7 1.36 2.66 2.86 2.97V19h2.34v-1.67c1.52-.29 2.72-1.16 2.73-2.77-.01-2.2-1.9-2.96-3.66-3.42z"/>
  </svg>
  Pix
</button>
              
            </div>
          </div>
        </div>
        
        <!-- Rodapé do modal -->
        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
          <button 
            @click="fecharModal"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500"
          >
            Cancelar
          </button>
          <button 
            @click="inscreverPlano"
            class="px-6 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500"
          >
            Confirmar assinatura
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Transições para o modal */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s, transform 0.3s;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* Prevenção de scroll quando o modal está aberto */
:global(body.overflow-hidden) {
  overflow: hidden;
}
</style>