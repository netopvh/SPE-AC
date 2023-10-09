<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Afastamento extends Model
{
    protected $table = 'afastamento';

    protected $primaryKey = 'id_afastamento';

    const CREATED_AT = 'data_criacao_afastamento';
    const UPDATED_AT = 'data_atualizacao_afastamento';

    protected $fillable = [
        'id_licenca_turmalina',
        'id_orgao',
        'matricula_afastamento',
        'contrato_afastamento',
        'descricao_afastamento',
        'data_inicio_afastamento',
        'data_fim_afastamento',
        'qtd_dias_afastamento',
        'data_criacao_afastamento',
        'data_atualizacao_afastamento'
    ];

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'matricula_afastamento', 'matricula_usuario');
    }
}
