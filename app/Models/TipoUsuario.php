<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoUsuario extends Model
{
    protected $table = 'tipo_usuario';

    protected $primaryKey = 'id_tipo_usuario';

    public $incrementing = true;

    const CREATED_AT = 'data_criacao_tipo_usuario';

    const UPDATED_AT = 'data_atualizacao_tipo_usuario';

    protected $fillable = [
        'id_tipo_usuario',
        'descricao_tipo_usuario',
        'temporario',
        'data_criacao_tipo_usuario',
        'data_atualizacao_tipo_usuario'
    ];
}
