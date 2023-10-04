<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ferias extends Model
{
    protected $table = 'ferias';

    protected $primaryKey = 'id_ferias';

    protected $dates = [
        'data_inicio_ferias',
        'data_fim_ferias',
        'data_criacao_ferias',
        'data_atualizacao_ferias'
    ];

    const CREATED_AT = 'data_criacao_ferias';
    const UPDATED_AT = 'data_atualizacao_ferias';

    protected $fillable = [
        'id_ferias_turmalina',
        'matricula_ferias',
        'contrato_ferias',
        'data_inicio_ferias',
        'data_fim_ferias',
        'qtd_dias_ferias',
        'data_criacao_ferias',
        'data_atualizacao_ferias'
    ];
}
