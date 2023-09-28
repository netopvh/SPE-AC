<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{
    Usuario,
    Orgao,
    Lotacao,
    Horario
};

class Importacao extends Model
{
    protected $table = 'importacao';

    protected $primaryKey = 'id_importacao';
    
    const CREATED_AT = 'data_criacao_importacao';
    const UPDATED_AT = 'data_atualizacao_importacao';

    protected $fillable = [
        'situacao_importacao',   
        'data_criacao_importacao', 
        'data_atualizacao_importacao'
    ];
}
