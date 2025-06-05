<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoPag extends Model
{
    use HasFactory;

    // Definir o nome da tabela
    protected $table = 'historicopag';

    // Definir os campos que podem ser preenchidos
    protected $fillable = ['nome', 'numero', 'descricao', 'status', 'cliente_id', 'reference_id', 'instancia', 'valor', 'custo', 'categoria_name'];

    // Relacionamento com o modelo Cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
