<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendario extends Model
{
    protected $table = 'calendario';

    protected $primaryKey = 'id_calendario';
    
    const CREATED_AT = 'data_criacao_calendario';
    const UPDATED_AT = 'data_atualizacao_calendario';

    protected $fillable = [
        'data_calendario',   
        'tipo_calendario',   
        'descricao_calendario',   
        'amparo_calendario',   
        'id_usuario_criacao_calendario',   
        'id_usuario_atualizacao_calendario',    
        'data_criacao_calendario', 
        'data_atualizacao_calendario'
    ];
}
