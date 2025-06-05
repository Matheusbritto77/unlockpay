<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    // Nome da tabela (opcional, se for 'pagamentos' Laravel já reconhece pelo nome padrão)
    protected $table = 'pagamentos';

    // Os atributos que podem ser atribuídos em massa
    protected $fillable = [
        'user_id',
        'reference',
        'status',
        'valor',
        'plan_id',
    ];

    // Se não estiver usando os timestamps (created_at, updated_at):
    public $timestamps = false;

    // Relacionamentos (opcional, caso futuramente queira usar)
    /*
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
    */
}