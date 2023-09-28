<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoUsuario extends Model
{
    protected $table = 'tipo_usuario';

    protected $primaryKey = 'id_tipo_usuario';
    
    const CREATED_AT = 'data_criacao_tipo_usuario';
    const UPDATED_AT = 'data_atualizacao_tipo_usuario';

    protected $fillable = [ 
        'descricao_tipo_usuario',  
        'data_criacao_tipo_usuario', 
        'data_atualizacao_tipo_usuario'
    ];
}
