<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPerfil extends Model
{
    protected $table = 'tipo_perfil';

    protected $primaryKey = 'id_tipo_perfil';
    
    const CREATED_AT = 'data_criacao_tipo_perfil';
    const UPDATED_AT = 'data_atualizacao_tipo_perfil';

    protected $fillable = [ 
        'descricao_tipo_perfil',  
        'data_criacao_tipo_perfil', 
        'data_atualizacao_tipo_perfil'
    ];
}
