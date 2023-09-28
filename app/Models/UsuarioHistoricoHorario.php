<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{
    Orgao,
    Lotacao,
    Horario,
    PerfilUsuario
};

class Usuario extends Model
{
    protected $table = 'usuario_historico_horario';

    protected $primaryKey = 'id_historico';
    
    const CREATED_AT = 'data_alteracao';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_usuario',   
        'id_horario',
        'data_alteracao'
    ];
}
