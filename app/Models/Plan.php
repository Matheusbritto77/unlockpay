<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    // Se o nome da tabela é 'plans', pode omitir a propriedade $table
    // protected $table = 'plans';

    protected $fillable = [
        'nome',
        'descricao',
        'preco',
        'duracao_dias',
        'tipo',
        'ativo',
    ];

    // Se quiser trabalhar com timestamps customizados
    // public $timestamps = true;

    /**
     * Relacionamento: planos podem ser associados a muitos usuários.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'plan_id');
    }
}