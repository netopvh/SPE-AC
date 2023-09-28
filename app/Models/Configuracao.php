<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracao extends Model
{
    protected $table = 'configuracao';

    protected $primaryKey = 'id_configuracao';
    
    const CREATED_AT = 'data_criacao_configuracao';
    const UPDATED_AT = 'data_atualizacao_configuracao';

    protected $fillable = [
        'chave_configuracao',     
        'valor_configuracao',     
        'id_usuario_criacao_configuracao',     
        'id_usuario_atualizacao_configuracao',     
        'data_criacao_configuracao', 
        'data_atualizacao_configuracao'
    ];
}
