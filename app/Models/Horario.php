<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $table = 'horario';

    protected $primaryKey = 'id_horario';

    const CREATED_AT = 'data_criacao_horario';
    const UPDATED_AT = 'data_atualizacao_horario';

    protected $fillable = [
        'entrada_1_horario',
        'saida_1_horario',
        'entrada_2_horario',
        'saida_2_horario',
        'situacao_horario',
        'id_usuario_criacao_horario',
        'id_usuario_atualizacao_horario',
        'data_criacao_horario',
        'data_atualizacao_horario'
    ];
}