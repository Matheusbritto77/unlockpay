<?php


namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\ScheduleHour;
use App\Models\ScheduleDay;
use App\Models\ScheduleExternalGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Instancia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\SchedulesPeriodo;

class ScheduleController extends Controller
{
    /**
 * List all schedules for the authenticated user.
 */
public function index()
{
    try {
        $schedules = Schedule::where('user_id', Auth::id())
            ->with(['hours', 'days', 'externalGroups', 'periodo'])
            ->get();
        
        Log::info('Lista de agendamentos recuperada para o usuário ID ' . Auth::id(), ['total' => $schedules->count()]);
        return response()->json($schedules);
    } catch (\Exception $e) {
        Log::error('Erro ao listar agendamentos para o usuário ID ' . Auth::id() . ': ' . $e->getMessage());
        return response()->json(['error' => 'Erro ao listar agendamentos.'], 500);
    }
}

    /**
 * Show a specific schedule for the authenticated user.
 */
public function show(Schedule $schedule)
{
    try {
        if ($schedule->user_id !== Auth::id()) {
            Log::warning('Acesso não autorizado ao agendamento ID ' . $schedule->id . ' pelo usuário ID ' . Auth::id());
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $schedule->load(['hours', 'days', 'externalGroups', 'periodo']);
        Log::info('Detalhes do agendamento ID ' . $schedule->id . ' recuperados para o usuário ID ' . Auth::id());
        return response()->json($schedule);
    } catch (\Exception $e) {
        Log::error('Erro ao exibir agendamento ID ' . $schedule->id . ' para o usuário ID ' . Auth::id() . ': ' . $e->getMessage());
        return response()->json(['error' => 'Erro ao exibir agendamento.'], 500);
    }
}
    /**
 * Create a new schedule.
 */
public function store(Request $request)
{
    $validated = $request->validate([
        'description' => 'nullable|string',
        'image' => 'nullable|file|mimes:jpg,jpeg,png|max:20048',
        'mode' => 'required|in:daily,specific_days',
        'frequency' => 'required|string|in:1 vez por dia,2 vezes por dia,3 vezes por dia,De hora em hora',
        'hours' => 'nullable|array|max:3',
        'hours.*' => 'required_if:frequency,1 vez por dia,2 vezes por dia,3 vezes por dia|date_format:H:i',
        'days' => 'nullable|array',
        'days.*' => 'required_if:mode,specific_days|date_format:d/m/Y',
        'group_ids' => 'required|array',
        'group_ids.*' => '',
        // Validação para período quando frequência é "De hora em hora"
        'period_start' => 'required_if:frequency,De hora em hora|date_format:H:i',
        'period_end' => 'required_if:frequency,De hora em hora|date_format:H:i|after:period_start',
    ]);

    // Verificar se a frequência exige horários e validar a quantidade
    if (in_array($validated['frequency'], ['1 vez por dia', '2 vezes por dia', '3 vezes por dia'])) {
        $expectedCount = (int) substr($validated['frequency'], 0, 1);
        if (count($validated['hours'] ?? []) !== $expectedCount) {
            return response()->json([
                'error' => "Você deve fornecer exatamente {$expectedCount} horário(s) para a frequência '{$validated['frequency']}'."
            ], 422);
        }
    }

    // Verificar se modo é specific_days e exige dias
    if ($validated['mode'] === 'specific_days' && empty($validated['days'])) {
        return response()->json(['error' => 'Você deve fornecer pelo menos um dia para o modo "Dias Específicos".'], 422);
    }

    // Upload de imagem se fornecida
    $imagePath = null;
    if ($request->hasFile('image')) {
        try {
            // Tentar salvar no driver 'public'
            $imagePath = $request->file('image')->store('schedule_images', 'local');
            Log::info("Imagem salva com sucesso no driver 'public': {$imagePath}");
        } catch (\Exception $e) {
            // Caso falhe, fazer fallback para o driver padrão (geralmente 'local' em storage/app)
            Log::warning("Falha ao salvar imagem no driver 'public': " . $e->getMessage() . ". Usando fallback para 'storage'.");
            $imagePath = $request->file('image')->store('schedule_images');
            Log::info("Imagem salva com sucesso no driver padrão (fallback): {$imagePath}");
        }
    }

    // Criação do agendamento
    $schedule = Schedule::create([
        'user_id' => Auth::id(),
        'description' => $validated['description'],
        'image_path' => $imagePath,
        'mode' => $validated['mode'],
        'frequency' => $validated['frequency'],
        'status' => 'active',
    ]);

    // Adicionar horários se fornecidos (não se for "De hora em hora")
    if ($validated['frequency'] !== 'De hora em hora' && !empty($validated['hours'])) {
        foreach ($validated['hours'] as $hour) {
            ScheduleHour::create([
                'schedule_id' => $schedule->id,
                'hour' => $hour,
            ]);
        }
    }

    // Adicionar período se frequência for "De hora em hora"
    if ($validated['frequency'] === 'De hora em hora') {
        SchedulesPeriodo::create([
            'schedules_id' => $schedule->id,
            'periodo_inicio' => $validated['period_start'],
            'periodo_fim' => $validated['period_end'],
        ]);
    }

    // Adicionar dias específicos se modo for "specific_days"
    if ($validated['mode'] === 'specific_days' && !empty($validated['days'])) {
        foreach ($validated['days'] as $day) {
            $date = \DateTime::createFromFormat('d/m/Y', $day)->format('Y-m-d');
            ScheduleDay::create([
                'schedule_id' => $schedule->id,
                'date' => $date,
            ]);
        }
    }

    // Relacionar group_ids externos ao agendamento
    if (!empty($validated['group_ids'])) {
        foreach ($validated['group_ids'] as $groupId) {
            ScheduleExternalGroup::create([
                'schedule_id' => $schedule->id,
                'group_id' => $groupId,
            ]);
        }
    }

    // Carregar relacionamentos para a resposta
    return response()->json(['message' => 'Agendamento criado com sucesso', 'schedule' => $schedule->load(['hours', 'days', 'externalGroups', 'periodo'])], 201);
}

    /**
 * Update an existing schedule.
 */
public function update(Request $request, Schedule $schedule)
{
    if ($schedule->user_id !== Auth::id()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $validated = $request->validate([
        'description' => 'nullable|string',
        'image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        'mode' => 'required|in:daily,specific_days',
        'frequency' => 'required|string|in:1 vez por dia,2 vezes por dia,3 vezes por dia,De hora em hora',
        'hours' => 'nullable|array|max:3',
        'hours.*' => 'required_if:frequency,1 vez por dia,2 vezes por dia,3 vezes por dia|date_format:H:i',
        'days' => 'nullable|array',
        'days.*' => 'required_if:mode,specific_days|date_format:d/m/Y',
        'group_ids' => 'required|array',
        'group_ids.*' => 'integer',
        // Validação para período quando frequência é "De hora em hora"
        'period_start' => 'required_if:frequency,De hora em hora|date_format:H:i',
        'period_end' => 'required_if:frequency,De hora em hora|date_format:H:i|after:period_start',
    ]);

    // Verificar se a frequência exige horários e validar a quantidade
    if (in_array($validated['frequency'], ['1 vez por dia', '2 vezes por dia', '3 vezes por dia'])) {
        $expectedCount = (int) substr($validated['frequency'], 0, 1);
        if (count($validated['hours'] ?? []) !== $expectedCount) {
            return response()->json([
                'error' => "Você deve fornecer exatamente {$expectedCount} horário(s) para a frequência '{$validated['frequency']}'."
            ], 422);
        }
    }

    // Verificar se modo é specific_days e exige dias
    if ($validated['mode'] === 'specific_days' && empty($validated['days'])) {
        return response()->json(['error' => 'Você deve fornecer pelo menos um dia para o modo "Dias Específicos".'], 422);
    }

    // Atualizar imagem se fornecida
    if ($request->hasFile('image')) {
        if ($schedule->image_path) {
            Storage::disk('public')->delete($schedule->image_path);
        }
        $schedule->image_path = $request->file('image')->store('schedule_images', 'public');
    }

    // Atualizar dados principais
    $schedule->update([
        'description' => $validated['description'],
        'mode' => $validated['mode'],
        'frequency' => $validated['frequency'],
    ]);

    // Atualizar horários (não se for "De hora em hora")
    if ($validated['frequency'] !== 'De hora em hora' && !empty($validated['hours'])) {
        $schedule->hours()->delete();
        foreach ($validated['hours'] as $hour) {
            ScheduleHour::create([
                'schedule_id' => $schedule->id,
                'hour' => $hour,
            ]);
        }
    } else {
        $schedule->hours()->delete(); // Remove horários se for "De hora em hora" ou se não houver horários fornecidos
    }

    // Atualizar período se frequência for "De hora em hora"
    if ($validated['frequency'] === 'De hora em hora') {
        // Deletar período existente associado ao agendamento, se houver
        $schedule->periodo()->delete();
        // Criar um novo registro de período com os valores atualizados
        SchedulesPeriodo::create([
            'schedules_id' => $schedule->id,
            'periodo_inicio' => $validated['period_start'],
            'periodo_fim' => $validated['period_end'],
        ]);
    } else {
        // Se a frequência não for "De hora em hora", deletar qualquer período existente
        $schedule->periodo()->delete();
    }

    // Atualizar dias específicos
    if ($validated['mode'] === 'specific_days' && !empty($validated['days'])) {
        $schedule->days()->delete();
        foreach ($validated['days'] as $day) {
            $date = \DateTime::createFromFormat('d/m/Y', $day)->format('Y-m-d');
            ScheduleDay::create([
                'schedule_id' => $schedule->id,
                'date' => $date,
            ]);
        }
    } else {
        $schedule->days()->delete(); // Remove dias se não for modo "specific_days"
    }

    // Atualizar group_ids externos associados
    if (!empty($validated['group_ids'])) {
        $schedule->externalGroups()->delete();
        foreach ($validated['group_ids'] as $groupId) {
            ScheduleExternalGroup::create([
                'schedule_id' => $schedule->id,
                'group_id' => $groupId,
            ]);
        }
    }

    return response()->json(['message' => 'Agendamento atualizado com sucesso', 'schedule' => $schedule->load(['hours', 'days', 'externalGroups', 'periodo'])]);
}

    /**
     * Delete a schedule.
     */
    public function destroy(Schedule $schedule)
    {
        if ($schedule->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($schedule->image_path) {
            Storage::disk('public')->delete($schedule->image_path);
        }

        $schedule->delete(); // Exclui o agendamento e, por cascata, os registros associados (horários, dias, grupos)
        return response()->json(['message' => 'Agendamento excluído com sucesso']);
    }

    /**
     * Toggle the status of a schedule between active and inactive.
     */
    public function toggleStatus(Schedule $schedule)
    {
        if ($schedule->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $newStatus = $schedule->status === 'active' ? 'inactive' : 'active';
        $schedule->update(['status' => $newStatus]);

        return response()->json(['message' => "Agendamento " . ($newStatus === 'active' ? 'ativado' : 'desativado') . " com sucesso", 'schedule' => $schedule]);
    }



 /**
 * Fetch groups from the external API using the user's instance name as sessionId.
 *
 * @return array|array Returns the list of groups on success or a JSON-structured error response on failure.
 */
public static function fetchGroupsFromApi()
{
    try {
        // API Token fornecido
        $apiToken = "asdfghjksjhggbnmfdfghjhgf";

        // Obter o ID do usuário autenticado
        $userId = Auth::id();

        if (!$userId) {
            Log::error('User not authenticated when fetching groups.');
            return [
                'success' => false,
                'error' => 'User not authenticated. Please login and try again.',
                'code' => 401
            ];
        }

        // Buscar a instância ativa do usuário
        $instance = Instancia::where('user_id', $userId)
            ->active()
            ->first();

        if (!$instance) {
            Log::error('No active instance found for user ID: ' . $userId);
            return [
                'success' => false,
                'error' => 'No active instance found for your account. Please create or activate an instance.',
                'code' => 404
            ];
        }

        // Usar o 'name' da instância como sessionId
        $sessionId = $instance->name;

        // Fazer a chamada para a API externa
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'x-api-key' => $apiToken,
        ])->get("https://dapleserver.com/client/getlistGrup/{$sessionId}");

        // Verificar se a requisição foi bem-sucedida
        if ($response->successful()) {
            $data = $response->json();
            if ($data['success'] === true && isset($data['groups']) && is_array($data['groups'])) {
                // Mapear os dados para um formato utilizável pelo frontend (id e name)
                $groups = array_map(function ($group) {
                    return [
                        'id' => $group['user'], // Usando 'user' como ID, conforme retorno da API
                        'name' => $group['name'],
                    ];
                }, $data['groups']);
                return [
                    'success' => true,
                    'groups' => $groups
                ];
            } else {
                Log::warning('API returned success false or no valid groups data for sessionId: ' . $sessionId);
                return [
                    'success' => false,
                    'error' => 'API returned invalid or empty groups data.',
                    'code' => 422
                ];
            }
        } else {
            Log::error('Failed to fetch groups from API. Status: ' . $response->status() . ', Response: ' . $response->body());
            return [
                'success' => false,
                'error' => 'Failed to fetch groups from external API. Status code: ' . $response->status(),
                'code' => $response->status()
            ];
        }
    } catch (\Exception $e) {
        Log::error('Error fetching groups from API: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
        return [
            'success' => false,
            'error' => 'An unexpected error occurred while fetching groups. Please try again later.',
            'code' => 500
        ];
    }
}

/**
 * Processa agendamentos pendentes para postagem em grupos via WhatsApp.
 * Deve ser chamada a cada minuto por um cron job ou similar.
 */
public function processScheduledPosts()
{
    try {
        // Obter todos os agendamentos ativos com suas relações
        $schedules = Schedule::where('status', 'active')
            ->with(['hours', 'days', 'externalGroups', 'periodo'])
            ->get();

        if ($schedules->isEmpty()) {
            Log::info('Nenhum agendamento ativo encontrado para processar.');
            return response()->json(['message' => 'Nenhum agendamento ativo para processar.']);
        }

        $currentTime = Carbon::now();
        $postsToProcess = [];

        foreach ($schedules as $schedule) {
            // Verificar se o agendamento foi atualizado nos últimos 10 minutos
            try {
                $lastUpdate = Carbon::parse($schedule->updated_at);
                if ($lastUpdate->diffInMinutes($currentTime) < 10) {
                    Log::info("Agendamento ID {$schedule->id} ignorado: atualizado recentemente.");
                    continue;
                }
            } catch (\Exception $e) {
                Log::error("Erro ao parsear updated_at do Agendamento ID {$schedule->id}: " . $e->getMessage());
                continue; // Ignora este agendamento se houver erro na data
            }

            // Verificar o modo do agendamento (daily ou specific_days)
            $shouldProcess = false;
            if ($schedule->mode === 'daily') {
                // Modo 'daily': postar todos os dias, sujeito à frequência
                $shouldProcess = true;
            } elseif ($schedule->mode === 'specific_days') {
                // Modo 'specific_days': verificar se hoje está na lista de dias específicos
                $days = [];
                try {
                    $days = $schedule->days->pluck('date')->map(function ($date) {
                        return Carbon::parse($date)->format('Y-m-d');
                    })->toArray();
                } catch (\Exception $e) {
                    Log::error("Erro ao parsear datas de dias específicos do Agendamento ID {$schedule->id}: " . $e->getMessage());
                }
                $currentDate = $currentTime->format('Y-m-d');
                if (in_array($currentDate, $days)) {
                    Log::info("Agendamento ID {$schedule->id} passou na verificação de dia específico.");
                    $shouldProcess = true;
                } else {
                    Log::info("Agendamento ID {$schedule->id} ignorado: dia atual não está na lista de dias específicos.");
                }
            }

            // Se o modo permitir o processamento, verificar a frequência
            if ($shouldProcess) {
                // Usar switch/case para tratar as frequências
                switch ($schedule->frequency) {
                    case 'De hora em hora':
                        // Verificar se passou pelo menos 1 hora desde a última atualização
                        if ($lastUpdate->diffInHours($currentTime) >= 1) {
                            // Verificar se existe um período associado ao agendamento
                            if ($schedule->periodo) {
                                try {
                                    // Obter periodo_inicio e periodo_fim
                                    $startTime = Carbon::createFromFormat('H:i:s', $schedule->periodo->periodo_inicio);
                                    if ($startTime === false) {
                                        $startTime = Carbon::createFromFormat('H:i', $schedule->periodo->periodo_inicio);
                                    }
                                    $endTime = Carbon::createFromFormat('H:i:s', $schedule->periodo->periodo_fim);
                                    if ($endTime === false) {
                                        $endTime = Carbon::createFromFormat('H:i', $schedule->periodo->periodo_fim);
                                    }

                                    // Ajustar para o dia atual para comparação
                                    $startTimeToday = Carbon::today()
                                        ->setHour($startTime->hour)
                                        ->setMinute($startTime->minute);
                                    $endTimeToday = Carbon::today()
                                        ->setHour($endTime->hour)
                                        ->setMinute($endTime->minute);

                                    // Verificar se o horário atual está dentro do período
                                    if ($currentTime->between($startTimeToday, $endTimeToday)) {
                                        Log::info("Agendamento ID {$schedule->id} adicionado: frequência 'De hora em hora', passou 1 hora e está dentro do período de postagem ({$startTimeToday->format('H:i')} - {$endTimeToday->format('H:i')}).");
                                        $postsToProcess[] = $schedule;
                                    } else {
                                        Log::info("Agendamento ID {$schedule->id} ignorado: frequência 'De hora em hora', mas fora do período de postagem ({$startTimeToday->format('H:i')} - {$endTimeToday->format('H:i')}).");
                                    }
                                } catch (\Exception $e) {
                                    Log::error("Erro ao parsear período do Agendamento ID {$schedule->id} (De hora em hora): " . $e->getMessage());
                                }
                            } else {
                                Log::warning("Agendamento ID {$schedule->id} ignorado: frequência 'De hora em hora', mas não há período associado.");
                            }
                        } else {
                            Log::info("Agendamento ID {$schedule->id} ignorado: não passou 1 hora desde a última atualização.");
                        }
                        break;

                    case '1 vez por dia':
                        // Verificar se há exatamente 1 registro de horário para este agendamento
                        $hours = ScheduleHour::where('schedule_id', $schedule->id)->get();
                        if ($hours->count() === 1) {
                            try {
                                $hour = $hours->first()->hour;
                                // Tentar parsear no formato H:i:s (ex.: "11:20:00")
                                $scheduledTime = Carbon::createFromFormat('H:i:s', $hour);
                                if ($scheduledTime === false) {
                                    // Caso falhe, tentar no formato H:i (ex.: "11:20")
                                    Log::warning("Formato H:i:s falhou para horário {$hour} do Agendamento ID {$schedule->id}, tentando H:i.");
                                    $scheduledTime = Carbon::createFromFormat('H:i', $hour);
                                }
                                $scheduledTimeToday = Carbon::today()
                                    ->setHour($scheduledTime->hour)
                                    ->setMinute($scheduledTime->minute);
                                if ($currentTime->between($scheduledTimeToday, $scheduledTimeToday->copy()->addMinutes(10))) {
                                    Log::info("Agendamento ID {$schedule->id} adicionado: horário {$hour} corresponde à hora atual (1 vez por dia).");
                                    $postsToProcess[] = $schedule;
                                }
                            } catch (\Exception $e) {
                                Log::error("Erro ao parsear horário do Agendamento ID {$schedule->id} (1 vez por dia, horário {$hour}): " . $e->getMessage());
                            }
                        } else {
                            Log::warning("Agendamento ID {$schedule->id} ignorado: esperava 1 horário, encontrou {$hours->count()} (1 vez por dia).");
                        }
                        break;

                    case '2 vezes por dia':
                        // Verificar se há exatamente 2 registros de horário para este agendamento
                        $hours = ScheduleHour::where('schedule_id', $schedule->id)->get();
                        if ($hours->count() === 2) {
                            foreach ($hours as $hourRecord) {
                                try {
                                    $hour = $hourRecord->hour;
                                    // Tentar parsear no formato H:i:s (ex.: "11:20:00")
                                    $scheduledTime = Carbon::createFromFormat('H:i:s', $hour);
                                    if ($scheduledTime === false) {
                                        // Caso falhe, tentar no formato H:i (ex.: "11:20")
                                        Log::warning("Formato H:i:s falhou para horário {$hour} do Agendamento ID {$schedule->id}, tentando H:i.");
                                        $scheduledTime = Carbon::createFromFormat('H:i', $hour);
                                    }
                                    $scheduledTimeToday = Carbon::today()
                                        ->setHour($scheduledTime->hour)
                                        ->setMinute($scheduledTime->minute);
                                    if ($currentTime->between($scheduledTimeToday, $scheduledTimeToday->copy()->addMinutes(10))) {
                                        Log::info("Agendamento ID {$schedule->id} adicionado: horário {$hour} corresponde à hora atual (2 vezes por dia).");
                                        $postsToProcess[] = $schedule;
                                        break; // Basta uma correspondência
                                    }
                                } catch (\Exception $e) {
                                    Log::error("Erro ao parsear horário do Agendamento ID {$schedule->id} (2 vezes por dia, horário {$hour}): " . $e->getMessage());
                                }
                            }
                        } else {
                            Log::warning("Agendamento ID {$schedule->id} ignorado: esperava 2 horários, encontrou {$hours->count()} (2 vezes por dia).");
                        }
                        break;

                    case '3 vezes por dia':
                        // Verificar se há exatamente 3 registros de horário para este agendamento
                        $hours = ScheduleHour::where('schedule_id', $schedule->id)->get();
                        if ($hours->count() === 3) {
                            foreach ($hours as $hourRecord) {
                                try {
                                    $hour = $hourRecord->hour;
                                    // Tentar parsear no formato H:i:s (ex.: "11:20:00")
                                    $scheduledTime = Carbon::createFromFormat('H:i:s', $hour);
                                    if ($scheduledTime === false) {
                                        // Caso falhe, tentar no formato H:i (ex.: "11:20")
                                        Log::warning("Formato H:i:s falhou para horário {$hour} do Agendamento ID {$schedule->id}, tentando H:i.");
                                        $scheduledTime = Carbon::createFromFormat('H:i', $hour);
                                    }
                                    $scheduledTimeToday = Carbon::today()
                                        ->setHour($scheduledTime->hour)
                                        ->setMinute($scheduledTime->minute);
                                    if ($currentTime->between($scheduledTimeToday, $scheduledTimeToday->copy()->addMinutes(10))) {
                                        Log::info("Agendamento ID {$schedule->id} adicionado: horário {$hour} corresponde à hora atual (3 vezes por dia).");
                                        $postsToProcess[] = $schedule;
                                        break; // Basta uma correspondência
                                    }
                                } catch (\Exception $e) {
                                    Log::error("Erro ao parsear horário do Agendamento ID {$schedule->id} (3 vezes por dia, horário {$hour}): " . $e->getMessage());
                                }
                            }
                        } else {
                            Log::warning("Agendamento ID {$schedule->id} ignorado: esperava 3 horários, encontrou {$hours->count()} (3 vezes por dia).");
                        }
                        break;

                    default:
                        Log::warning("Agendamento ID {$schedule->id} ignorado: frequência desconhecida '{$schedule->frequency}'.");
                        break;
                }
            }
        }

        // Processar a fila de postagens
        if (!empty($postsToProcess)) {
            Log::info("Processando " . count($postsToProcess) . " agendamentos para postagem.");
            foreach ($postsToProcess as $schedule) {
                $this->postToGroups($schedule);
            }
        } else {
            Log::info('Nenhum agendamento na hora de ser postado.');
        }

        return response()->json(['message' => 'Processamento de agendamentos concluído.', 'processed' => count($postsToProcess)]);
    } catch (\Exception $e) {
        Log::error('Erro ao processar agendamentos: ' . $e->getMessage());
        return response()->json(['error' => 'Erro ao processar agendamentos.'], 500);
    }
}

   /**
 * Realiza a postagem de um agendamento nos grupos vinculados via API do WhatsApp.
 *
 * @param Schedule $schedule
 */
private function postToGroups(Schedule $schedule)
{
    try {
        // Obter a instância ativa do usuário
        $instance = Instancia::where('user_id', $schedule->user_id)
            ->active()
            ->first();

        if (!$instance) {
            Log::error("Nenhuma instância ativa encontrada para o usuário ID {$schedule->user_id} no agendamento ID {$schedule->id}.");
            return;
        }

        $instanciaName = $instance->name;
        $apiUrl = "https://dapleserver.com/client/sendMessage/{$instanciaName}";
        $apiToken = "asdfghjksjhggbnmfdfghjhgf"; // Substitua pelo token real da API do WhatsApp

        // Obter os IDs dos grupos vinculados ao agendamento
        $groupIds = $schedule->externalGroups->pluck('group_id')->toArray();

        if (empty($groupIds)) {
            Log::warning("Nenhum grupo vinculado ao agendamento ID {$schedule->id}.");
            return;
        }

        // Preparar a mensagem de texto
        $mensagemTexto = $schedule->description ?: "Mensagem agendada sem descrição.";
        $hasImage = !empty($schedule->image_path);

        foreach ($groupIds as $groupId) {
            $chatId = "{$groupId}@g.us";

            // Enviar imagem com texto como caption, se houver imagem
            if ($hasImage) {
                try {
                    // Gerar a URL pública da imagem usando asset()
                    $imageUrl = asset('public/app/public/' . $schedule->image_path);

                    // Primeira tentativa: Enviar como MessageMediaFromURL com caption
                    $mensagemImagemPayload = [
                        'chatId' => $chatId,
                        'contentType' => 'MessageMediaFromURL',
                        'content' => $imageUrl, // URL direta como conteúdo
                        'options' => [
                            'caption' => $mensagemTexto // Adiciona o texto como legenda da imagem
                        ]
                    ];

                    Log::info("Enviando imagem via URL (MessageMediaFromURL) com caption para o grupo {$chatId} do agendamento ID {$schedule->id}: {$imageUrl}");
                    $imageResponse = Http::withHeaders([
                        'x-api-key' => $apiToken,
                        'Content-Type' => 'application/json'
                    ])->post($apiUrl, $mensagemImagemPayload);

                    if (!$imageResponse->successful()) {
                        Log::error("Falha ao enviar imagem como MessageMediaFromURL com caption para o grupo {$chatId}: " . $imageResponse->body());
                        
                        // Segunda tentativa: Tentar como MessageMedia (se a API suportar envio de arquivo) com caption
                        try {
                            $imageFullPath = public_path('app/public/' . $schedule->image_path);
                            if (file_exists($imageFullPath)) {
                                Log::info("Tentando envio como MessageMedia com arquivo e caption para o grupo {$chatId}.");
                                $imageResponse = Http::withHeaders([
                                    'x-api-key' => $apiToken
                                ])->attach('file', file_get_contents($imageFullPath), basename($imageFullPath))
                                  ->post($apiUrl, [
                                      'chatId' => $chatId,
                                      'contentType' => 'MessageMedia',
                                      'options' => [
                                          'caption' => $mensagemTexto // Adiciona o texto como legenda da imagem
                                      ]
                                  ]);

                                if (!$imageResponse->successful()) {
                                    Log::error("Falha ao enviar imagem como MessageMedia com caption para o grupo {$chatId}: " . $imageResponse->body());
                                } else {
                                    Log::info("Imagem enviada com sucesso como MessageMedia com caption para o grupo {$chatId}.");
                                }
                            } else {
                                Log::warning("Imagem não encontrada no caminho {$imageFullPath} para o agendamento ID {$schedule->id}.");
                            }
                        } catch (\Exception $e) {
                            Log::error("Erro ao tentar enviar imagem como MessageMedia com caption para o grupo {$chatId}: " . $e->getMessage());
                        }
                    } else {
                        Log::info("Imagem enviada com sucesso como MessageMediaFromURL com caption para o grupo {$chatId}.");
                    }
                } catch (\Exception $e) {
                    Log::error("Erro ao processar envio de imagem com caption para o grupo {$chatId} do agendamento ID {$schedule->id}: " . $e->getMessage());
                    // Caso falhe o envio da imagem, enviar apenas o texto como fallback
                    $mensagemTextoPayload = [
                        'chatId' => $chatId,
                        'contentType' => 'string',
                        'content' => $mensagemTexto,
                    ];

                    Log::info("Enviando apenas mensagem de texto como fallback para o grupo {$chatId} do agendamento ID {$schedule->id}.");
                    $response = Http::withHeaders([
                        'x-api-key' => $apiToken,
                        'Content-Type' => 'application/json'
                    ])->post($apiUrl, $mensagemTextoPayload);

                    if (!$response->successful()) {
                        Log::error("Falha ao enviar mensagem de texto como fallback para o grupo {$chatId}: " . $response->body());
                    }
                }
            } else {
                // Caso não haja imagem, enviar apenas o texto
                $mensagemTextoPayload = [
                    'chatId' => $chatId,
                    'contentType' => 'string',
                    'content' => $mensagemTexto,
                ];

                Log::info("Enviando mensagem de texto para o grupo {$chatId} do agendamento ID {$schedule->id}.");
                $response = Http::withHeaders([
                    'x-api-key' => $apiToken,
                    'Content-Type' => 'application/json'
                ])->post($apiUrl, $mensagemTextoPayload);

                if (!$response->successful()) {
                    Log::error("Falha ao enviar mensagem de texto para o grupo {$chatId}: " . $response->body());
                }
            }
        }

        // Atualizar o campo updated_at para evitar postagens repetidas
        $schedule->touch(); // Atualiza o campo updated_at com a hora atual
        Log::info("Postagem concluída para o agendamento ID {$schedule->id}.");
    } catch (\Exception $e) {
        Log::error("Erro ao postar mensagem do agendamento ID {$schedule->id}: " . $e->getMessage());
    }
}
}