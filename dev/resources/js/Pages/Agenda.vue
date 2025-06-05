<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2'; // Importação do SweetAlert2

const isLoading = ref(false);
const showModal = ref(false);
const isSpecificDaysMode = ref(false); // Controla se está no modo de dias específicos
const isEditing = ref(false); // Controla se está editando um agendamento existente
const currentSchedule = ref(null); // Armazena o agendamento sendo editado
const schedules = ref([]); // Armazena a lista de agendamentos do usuário

// Grupos disponíveis (carregados da API)
const availableGroups = ref([]);
const selectedGroups = ref([]);
const selectedImage = ref(null);
const description = ref('');
const schedulingType = ref('1 vez por dia');
const schedulingHours = ref(['08:00', '12:00', '16:00']);
const frequencyOptions = ['1 vez por dia', '2 vezes por dia', '3 vezes por dia', 'De hora em hora'];

// Para agendamento em dias específicos
const currentDate = new Date();
const currentMonth = ref(currentDate.getMonth());
const currentYear = ref(currentDate.getFullYear());
const selectedDays = ref([]); // Armazena os dias selecionados com suas configurações
const dayFrequency = ref({}); // Armazena a frequência por dia selecionado (não usado atualmente, mantido por compatibilidade)

// Variáveis para o período de hora em hora
const periodStart = ref('08:00'); // Hora de início padrão
const periodEnd = ref('20:00');  // Hora de fim padrão

// Computed para controlar a quantidade de campos de horário exibidos
const numberOfTimeInputs = computed(() => {
    if (schedulingType.value === 'De hora em hora') return 0;
    return parseInt(schedulingType.value.split(' ')[0]) || 0;
});

// Computed para gerar os dias do mês atual no calendário (limitado para um layout menor)
const calendarDays = computed(() => {
    const firstDay = new Date(currentYear.value, currentMonth.value, 1);
    const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0);
    const days = [];
    const startDay = firstDay.getDay(); // Dia da semana do primeiro dia

    // Preenche os dias anteriores com vazio para alinhar o calendário
    for (let i = 0; i < startDay; i++) {
        days.push({ day: '', disabled: true });
    }

    // Preenche os dias do mês
    for (let day = 1; day <= lastDay.getDate(); day++) {
        days.push({ day, disabled: false });
    }

    return days;
});

// Carregar grupos da API ao montar o componente
onMounted(async () => {
    await fetchGroups();
    await fetchSchedules();
});

// Função para buscar grupos da API
const fetchGroups = async () => {
    try {
        const response = await axios.get('/groups');
        if (response.data.groups) {
            availableGroups.value = response.data.groups;
           
        } else {
           
            console.error('Failed to fetch groups:', response.data.error);
        }
    } catch (error) {
       
        console.error('Error fetching groups:', error);
    }
};

// Função para buscar agendamentos do usuário
const fetchSchedules = async () => {
    try {
        const response = await axios.get('/schedules');
        schedules.value = response.data;
       
    } catch (error) {
       
        console.error('Error fetching schedules:', error);
    }
};

// Função para mudar o mês do calendário
const changeMonth = (direction) => {
    currentMonth.value += direction;
    if (currentMonth.value < 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else if (currentMonth.value > 11) {
        currentMonth.value = 0;
        currentYear.value++;
    }
};

// Função para simular carregamento do botão giratório
const startLoading = () => {
    isLoading.value = true;
    setTimeout(() => {
        isLoading.value = false;
        showModal.value = true;
    }, 1500);
};

// Função para fechar o modal
const closeModal = () => {
    showModal.value = false;
    resetForm();
};

// Função para resetar o formulário
const resetForm = () => {
    selectedGroups.value = [];
    selectedImage.value = null;
    description.value = '';
    schedulingType.value = '1 vez por dia';
    schedulingHours.value = ['08:00', '12:00', '16:00'];
    isSpecificDaysMode.value = false;
    isEditing.value = false;
    currentSchedule.value = null;
    selectedDays.value = [];
    dayFrequency.value = {};
    periodStart.value = '08:00'; // Reset para valor padrão
    periodEnd.value = '20:00';  // Reset para valor padrão
};

// Função para lidar com o upload de imagem
const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        selectedImage.value = file; // Armazena o arquivo real para envio
        
    } else {
        
    }
};

// Função para selecionar/deselecionar um grupo como tag
const toggleGroupSelection = (group) => {
    const index = selectedGroups.value.findIndex(g => g.id === group.id);
    if (index === -1) {
        selectedGroups.value.push(group);
       
    } else {
        selectedGroups.value.splice(index, 1);
       
    }
};

// Função para atualizar os horários
const updateSchedulingHour = (index, value) => {
    schedulingHours.value[index] = value;
   
};

const toggleDaySelection = (day) => {
    if (!day.disabled) {
        const formattedDay = String(day.day).padStart(2, '0'); // Garante dois dígitos para o dia
        const formattedMonth = String(currentMonth.value + 1).padStart(2, '0'); // Garante dois dígitos para o mês
        const dateStr = `${formattedDay}/${formattedMonth}/${currentYear.value}`;
        const index = selectedDays.value.indexOf(dateStr);
        if (index === -1) {
            selectedDays.value.push(dateStr);
            dayFrequency.value[dateStr] = schedulingType.value; // Usa a frequência atual como padrão
           
        } else {
            selectedDays.value.splice(index, 1);
            delete dayFrequency.value[dateStr];
            
        }
    }
};

// Função para togglar o modo de dias específicos
const toggleSpecificDaysMode = () => {
    isSpecificDaysMode.value = !isSpecificDaysMode.value;
   
};

const editSchedule = (schedule) => {
    currentSchedule.value = schedule;
    isEditing.value = true;
    description.value = schedule.description || '';
    schedulingType.value = schedule.frequency || '1 vez por dia';
    isSpecificDaysMode.value = schedule.mode === 'specific_days';
    
    // Carregar horários, garantindo que estejam no formato H:i (ex: 08:00)
    if (schedule.hours && schedule.hours.length > 0) {
        schedulingHours.value = schedule.hours.map(h => {
            if (h.hour && /^[0-2][0-9]:[0-5][0-9]$/.test(h.hour)) {
                return h.hour;
            }
            return '08:00';
        }).concat(['08:00', '12:00', '16:00']).slice(0, 3);
    } else {
        schedulingHours.value = ['08:00', '12:00', '16:00'];
    }

    // Carregar período para "De hora em hora"
    if (schedulingType.value === 'De hora em hora' && schedule.period) {
        periodStart.value = schedule.period.start || '08:00';
        periodEnd.value = schedule.period.end || '20:00';
    } else {
        periodStart.value = '08:00';
        periodEnd.value = '20:00';
    }

    // Carregar dias específicos com formatação dd/mm/yyyy
    if (schedule.days && schedule.days.length > 0) {
        selectedDays.value = schedule.days.map(d => {
            const date = new Date(d.date);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        });
    } else {
        selectedDays.value = [];
    }

    // Carregar grupos selecionados
    if (schedule.external_groups && schedule.external_groups.length > 0) {
        selectedGroups.value = schedule.external_groups.map(g => {
            const foundGroup = availableGroups.value.find(ag => ag.id === g.group_id);
            return foundGroup || { id: g.group_id, name: `Grupo ${g.group_id}` };
        }).filter(g => g.id);
    } else {
        selectedGroups.value = [];
    }

    showModal.value = true;
    
};

// Função para alternar o status de um agendamento
const toggleScheduleStatus = async (scheduleId) => {
    try {
        const response = await axios.patch(`/schedules/${scheduleId}/toggle-status`);
        Swal.fire({
            icon: 'success',
            title: 'Sucesso',
            text: 'Status do agendamento alterado com sucesso!',
            timer: 1500,
            showConfirmButton: false
        });
        // Atualizar a lista de agendamentos
        const index = schedules.value.findIndex(s => s.id === scheduleId);
        if (index !== -1) {
            schedules.value[index] = response.data.schedule;
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Erro ao alterar status do agendamento: ' + (error.message || 'Erro desconhecido'),
        });
        console.error('Erro ao alterar status do agendamento:', error);
    }
};

// Função para excluir um agendamento
const deleteSchedule = async (scheduleId) => {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Confirmação',
        text: 'Tem certeza que deseja excluir este agendamento?',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await axios.delete(`/schedules/${scheduleId}`);
        Swal.fire({
            icon: 'success',
            title: 'Sucesso',
            text: 'Agendamento excluído com sucesso!',
            timer: 1500,
            showConfirmButton: false
        });
        // Remover da lista de agendamentos
        schedules.value = schedules.value.filter(s => s.id !== scheduleId);
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Erro ao excluir agendamento: ' + (error.message || 'Erro desconhecido'),
        });
        console.error('Erro ao excluir agendamento:', error);
    }
};

// Função para salvar (criar ou atualizar) um agendamento
const saveSchedule = async () => {
    const formData = new FormData();
    formData.append('description', description.value);
    if (selectedImage.value) {
        formData.append('image', selectedImage.value);
    }
    formData.append('mode', isSpecificDaysMode.value ? 'specific_days' : 'daily');
    formData.append('frequency', schedulingType.value);
    if (schedulingType.value !== 'De hora em hora') {
        schedulingHours.value.slice(0, numberOfTimeInputs.value).forEach((hour, index) => {
            formData.append(`hours[${index}]`, hour);
        });
    } else {
        // Enviar período para "De hora em hora"
        formData.append('period_start', periodStart.value);
        formData.append('period_end', periodEnd.value);
    }
    if (isSpecificDaysMode.value) {
        selectedDays.value
            .filter(day => day && day.match(/^\d{2}\/\d{2}\/\d{4}$/))
            .forEach((day, index) => {
                formData.append(`days[${index}]`, day);
            });
    }
    selectedGroups.value.forEach((group, index) => {
        formData.append(`group_ids[${index}]`, group.id);
    });

    try {
        let response;
        if (isEditing.value && currentSchedule.value) {
            response = await axios.post(`/schedules/${currentSchedule.value.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            Swal.fire({
                icon: 'success',
                title: 'Sucesso',
                text: 'Agendamento atualizado com sucesso!',
                timer: 1500,
                showConfirmButton: false
            });
            const index = schedules.value.findIndex(s => s.id === currentSchedule.value.id);
            if (index !== -1) {
                schedules.value[index] = response.data.schedule;
            }
        } else {
            response = await axios.post('/schedules', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            Swal.fire({
                icon: 'success',
                title: 'Sucesso',
                text: 'Agendamento criado com sucesso!',
                timer: 1500,
                showConfirmButton: false
            });
            schedules.value.push(response.data.schedule);
        }
        closeModal();
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Erro ao salvar agendamento: ' + (error.response ? error.response.data.message : error.message || 'Erro desconhecido'),
        });
        console.error('Erro ao salvar agendamento:', error.response ? error.response.data : error);
    }
};
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Whatsapp Agendamentos
            </h2>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-md sm:rounded-xl p-8 flex flex-col items-center">
                    <button
                        @click="startLoading"
                        class="group flex items-center justify-center w-16 h-16 bg-green-600 text-white rounded-full hover:bg-green-700 focus:outline-none transition duration-300 shadow-lg relative"
                        :class="{ 'animate-spin': isLoading }"
                    >
                        <span v-if="!isLoading" class="text-3xl group-hover:scale-110 transition-transform">+</span>
                        <div v-else class="w-14 h-14 border-4 border-white border-t-transparent rounded-full"></div>
                    </button>
                    <p v-if="!isLoading" class="mt-4 text-center text-gray-700 font-medium tracking-wide">Agendar Agora</p>

                    <!-- Lista de Agendamentos -->
                    <div class="mt-8 w-full">
                        <h3 class="text-lg font-semibold mb-6 text-gray-800">Meus Agendamentos</h3>
                        <div v-if="schedules.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="schedule in schedules" :key="schedule.id" class="border border-gray-200 rounded-xl p-5 bg-white shadow-sm hover:shadow-md transition duration-300 flex flex-col justify-between">
                                <div>
                                    <p class="font-medium text-gray-800">{{ schedule.description || 'Sem Descrição' }}</p>
                                    <p class="text-sm text-gray-500 mt-1">Modo: {{ schedule.mode === 'specific_days' ? 'Dias Específicos' : 'Diário' }}</p>
                                    <p class="text-sm text-gray-500">Frequência: {{ schedule.frequency }}</p>
                                    <p class="text-sm text-gray-500">Status: <span :class="schedule.status === 'active' ? 'text-green-600' : 'text-red-600'">{{ schedule.status === 'active' ? 'Ativo' : 'Inativo' }}</span></p>
                                </div>
                                <div class="flex space-x-3 mt-4">
                                    <button @click="editSchedule(schedule)" class="px-4 py-2 bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 transition text-sm font-medium">
                                        Editar
                                    </button>
                                    <button @click="toggleScheduleStatus(schedule.id)" class="px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition text-sm font-medium">
                                        {{ schedule.status === 'active' ? 'Desativar' : 'Ativar' }}
                                    </button>
                                    <button @click="deleteSchedule(schedule.id)" class="px-4 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition text-sm font-medium">
                                        Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 text-center py-6">Nenhum agendamento encontrado.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-2xl p-6 shadow-xl relative transition-all duration-300 w-full max-w-5xl max-h-[60vh] overflow-y-auto">
                <h3 class="text-xl font-bold mb-6 text-gray-800 text-center tracking-tight">{{ isEditing ? 'Editar Agendamento' : 'Criar Agendamento' }}</h3>
                
                <form @submit.prevent="saveSchedule" class="space-y-6">
                    <div class="flex space-x-6">
                        <!-- Coluna Esquerda: Imagem e Descrição -->
                        <div class="w-5/12 space-y-5">
                            <!-- Upload de Imagem -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Imagem (Opcional)</label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="handleImageUpload"
                                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                                />
                               
                                
                            </div>

                            <!-- Descrição -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                                <textarea
                                    v-model="description"
                                    rows="4"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm transition"
                                ></textarea>
                            </div>

                            <!-- Botão para Alternar Modo de Dias Específicos -->
                            <div class="mt-2">
                                <button
                                    type="button"
                                    @click="toggleSpecificDaysMode"
                                    class="px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 focus:outline-none text-sm font-medium transition"
                                >
                                    {{ isSpecificDaysMode ? 'Voltar para Posts Diários' : 'Programar para Dias Específicos' }}
                                </button>
                            </div>
                        </div>

                        <!-- Coluna Direita: Configurações -->
                        <div class="w-7/12 space-y-5">
                            <!-- Tipo de Agendamento (Frequência) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Frequência {{ isSpecificDaysMode ? '(Para Dias Selecionados)' : '(Posts Diários)' }}</label>
                                <select
                                    v-model="schedulingType"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm transition"
                                >
                                    <option v-for="option in frequencyOptions" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>
                            </div>

                            <!-- Horários Dinâmicos (apenas se não for hora em hora) -->
                            <div v-if="schedulingType !== 'De hora em hora'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Horário(s)</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <input
                                        v-for="index in numberOfTimeInputs"
                                        :key="index"
                                        type="time"
                                        :value="schedulingHours[index - 1]"
                                        @input="updateSchedulingHour(index - 1, $event.target.value)"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm transition"
                                    />
                                </div>
                            </div>

                            <!-- Campos de Período para "De hora em hora" -->
                            <div v-else class="space-y-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Período (De hora em hora)</label>
                                <div class="flex space-x-4">
                                    <div class="w-1/2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Hora de Início</label>
                                        <input
                                            type="time"
                                            v-model="periodStart"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm transition"
                                        />
                                    </div>
                                    <div class="w-1/2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Hora de Fim</label>
                                        <input
                                            type="time"
                                            v-model="periodEnd"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm transition"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Seleção de Grupos como Tags -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Grupos</label>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span
                                        v-for="group in availableGroups"
                                        :key="group.id"
                                        @click="toggleGroupSelection(group)"
                                        class="px-3 py-1.5 text-sm rounded-full cursor-pointer transition duration-200 border"
                                        :class="{
                                            'bg-green-50 text-green-800 border-green-300': selectedGroups.some(g => g.id === group.id),
                                            'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100': !selectedGroups.some(g => g.id === group.id)
                                        }"
                                    >
                                        {{ group.name }}
                                    </span>
                                </div>
                                <div v-if="selectedGroups.length > 0" class="mt-2">
                                    <p class="text-sm text-gray-600">Selecionados: {{ selectedGroups.map(g => g.name).join(', ') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calendário e Lista de Dias Selecionados -->
                    <div v-if="isSpecificDaysMode" class="mt-6 border-t border-gray-200 pt-4 flex space-x-6">
                        <!-- Calendário -->
                        <div class="w-3/5 bg-gray-50 p-3 rounded-xl shadow-sm">
                            <div class="flex justify-between items-center mb-3 text-sm">
                                <button
                                    type="button"
                                    @click="changeMonth(-1)"
                                    class="px-2 py-1 bg-gray-200 rounded-lg hover:bg-gray-300 transition"
                                >
                                    &lt;
                                </button>
                                <span class="font-medium text-gray-800">
                                    {{ new Date(currentYear, currentMonth).toLocaleString('pt-BR', { month: 'long', year: 'numeric' }) }}
                                </span>
                                <button
                                    type="button"
                                    @click="changeMonth(1)"
                                    class="px-2 py-1 bg-gray-200 rounded-lg hover:bg-gray-300 transition"
                                >
                                    &gt;
                                </button>
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-600">
                                <div v-for="day in ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']" :key="day">
                                    {{ day }}
                                </div>
                            </div>
                            <div class="grid grid-cols-7 gap-1 mt-2 text-center text-xs">
                                <div
                                    v-for="day in calendarDays"
                                    :key="day.day"
                                    @click="toggleDaySelection(day)"
                                    class="p-1.5 rounded-full cursor-pointer transition duration-200"
                                    :class="{
                                        'bg-green-100 text-green-800': !day.disabled && selectedDays.includes(`${day.day}/${currentMonth + 1}/${currentYear}`),
                                        'text-gray-400': day.disabled,
                                        'hover:bg-gray-200': !day.disabled && !selectedDays.includes(`${day.day}/${currentMonth + 1}/${currentYear}`)
                                    }"
                                >
                                    {{ day.day || '' }}
                                </div>
                            </div>
                        </div>

                        <!-- Lista de Dias Selecionados -->
                        <div v-if="selectedDays.length > 0" class="w-2/5 space-y-2">
                            <h5 class="text-sm font-medium text-gray-700">Dias Selecionados</h5>
                            <div class="max-h-28 overflow-y-auto pr-2">
                                <div v-for="day in selectedDays" :key="day" class="text-xs text-gray-700 border-b border-gray-100 pb-1">
                                    <span>{{ day }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200 mt-4">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:outline-none text-sm font-medium transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none text-sm font-medium transition"
                        >
                            {{ isEditing ? 'Atualizar' : 'Agendar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>