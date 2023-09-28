<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioPadrao extends Model
{
    protected $table = 'horario_padrao';

    protected $primaryKey = 'id_horario_padrao';
    
    const CREATED_AT = 'data_cadastro';
    const UPDATED_AT = 'data_alteracao';

    protected $fillable = [
        'id_orgao',   
        'id_horario',
        'id_usuario_cadastro',
        'id_usuario_alteracao',
        'status',
    ];
}
