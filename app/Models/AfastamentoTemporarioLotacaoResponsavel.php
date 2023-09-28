<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Usuario;

class AfastamentoTemporarioLotacaoResponsavel extends Model
{
    protected $table = 'afastamentos_temporario_lotacao_responsavel';

    protected $primaryKey = 'id_afastamento_temporario';
    
    const CREATED_AT = 'data_cadastro';
    const UPDATED_AT = 'data_atualizacao';

    protected $fillable = [
        'id_afastamento_temporario',
        'id_lotacao',
        'id_usuario',
        'id_substituto',
        'data_inicial',
        'data_final', 
        'id_usuario_cadastro',
        'data_cadastro',
        'id_usuario_atualizacao',
        'data_atualizacao',
        'status'
    ];

    public function Usuario() {
        return $this->belongsTo(new Usuario, 'id_usuario', 'id_usuario');
    }

    public function Substituto() {
        return $this->belongsTo(new Usuario, 'id_substituto', 'id_usuario');
    }
}
