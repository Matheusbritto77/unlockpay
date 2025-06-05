<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pelicula extends Model
{
    use HasFactory;

    // Define o nome da tabela associada ao modelo (opcional, se o nome da tabela seguir a convenção do Laravel, não é necessário)
    protected $table = 'peliculas';

    // Define os campos que podem ser preenchidos (mass assignable)
    protected $fillable = [
        'modelo',
        'modelos_compativeis',
    ];

    // Define os campos que devem ser escondidos em respostas JSON (opcional)
    protected $hidden = [];

    // Define os campos que serão tratados como datas (opcional, já que created_at é padrão)
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    // Se precisar de relações com outras tabelas, você pode defini-las aqui
    // Exemplo: public function relacao() { return $this->belongsTo(OutraClasse::class); }
}