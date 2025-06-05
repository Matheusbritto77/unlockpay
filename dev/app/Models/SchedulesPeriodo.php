<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchedulesPeriodo extends Model
{
    use HasFactory;

    // Definir o nome da tabela, se não seguir a convenção padrão do Laravel
    protected $table = 'schedules_periodo';

    // Campos que podem ser preenchidos em massa (mass assignment)
    protected $fillable = [
        'periodo_inicio',
        'periodo_fim',
        'schedules_id',
    ];

    // Campos que devem ser tratados como datas/horas
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    // Relacionamento com a tabela schedules (um período pertence a um agendamento)
    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedules_id', 'id');
    }
}