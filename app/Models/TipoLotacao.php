<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoLotacao extends Model
{
    protected $table = 'tipo_lotacao';

    protected $primaryKey = 'id_tipo_lotacao';
    
    const CREATED_AT = 'data_cadastro_tipo_lotacao';
    const UPDATED_AT = 'data_atualizacao_tipo_lotacao';

    protected $fillable = [
        'tipo_lotacao',    
        'data_cadastro_tipo_lotacao',
        'data_atualizacao_tipo_lotacao',    
        'status_tipo_lotacao'
    ];
}
