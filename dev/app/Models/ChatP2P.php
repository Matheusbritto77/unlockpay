<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatP2P extends Model
{
    // Nome da tabela (caso não siga o padrão Laravel)
    protected $table = 'chatp2p';

    // A chave primária não é auto_increment nem int padrão
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'unsignedBigInteger';

    // Laravel não usa created_at/updated_at nessa tabela
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip',
        'port',
        'last_seen',
    ];

    protected $casts = [
        'user_id'  => 'integer',
        'ip'       => 'string',
        'port'     => 'integer',
        'last_seen'=> 'datetime',
    ];

    /**
     * Relação com o usuário dono do peer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
