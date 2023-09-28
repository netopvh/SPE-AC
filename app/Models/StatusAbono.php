<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{
    Usuario,
    Orgao,
    Lotacao,
    Horario
};

class StatusAbono extends Model
{
    protected $table = 'status_abono';

    protected $primaryKey = 'id_status_abono';
    
    const CREATED_AT = 'data_criacao_status_abono';
    const UPDATED_AT = 'data_atualizacao_status_abono';

    protected $fillable = [   
        'descricao_status_abono',
        'data_criacao_status_abono',
        'data_atualizacao_status_abono'
    ];
}
