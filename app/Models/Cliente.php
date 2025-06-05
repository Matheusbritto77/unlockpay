<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    // Define a tabela associada a este modelo
    protected $table = 'clientes';

    // Define os campos que podem ser preenchidos em massa (mass assignment)
    protected $fillable = [
        'nome',
        'numero',
        'user_id',
    ];

    // Define o relacionamento com o modelo User
    public function user()
    {
        return $this->belongsTo(User::class);  // Relacionamento de muitos para um
    }
}
