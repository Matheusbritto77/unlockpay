<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instancia extends Model
{
    use HasFactory;

    /**
     * O nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'instancias';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
        'user_id',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtém o usuário proprietário desta instância.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Escopo de consulta para filtrar instâncias ativas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Escopo de consulta para filtrar instâncias inativas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Verifica se esta instância está ativa.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Ativa esta instância.
     *
     * @return bool
     */
    public function activate()
    {
        $this->status = 'active';
        return $this->save();
    }

    /**
     * Desativa esta instância.
     *
     * @return bool
     */
    public function deactivate()
    {
        $this->status = 'inactive';
        return $this->save();
    }
}