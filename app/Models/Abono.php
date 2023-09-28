<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{
    Usuario,
    Orgao,
    Lotacao,
    Horario,
	AbonoHorario,
    StatusAbono
};

class Abono extends Model
{
    protected $table = 'abono';

    protected $primaryKey = 'id_abono';
    
    const CREATED_AT = 'data_criacao_abono';
    const UPDATED_AT = 'data_atualizacao_abono';

    protected $fillable = [   
        'id_usuario_responsavel',
        'id_lotacao',
        'data_abono',
        'data_final_abono',
        'motivo_abono',
        'motivo_resposta_abono',
        'id_status_abono',
        'mensagem_abono',
        'mensagem_indeferido_abono',
        'periodo_abono',
        'tipo_documento',
        'diretorio_documento',
        'situacao_abono',
        'id_usuario_criacao_abono',
        'id_usuario_atualizacao_abono',
        'data_criacao_abono',
        'data_atualizacao_abono'
    ];

    public function Usuario() {
        return $this->belongsTo(new Usuario, 'id_usuario_criacao_abono', 'id_usuario');
    }

    public function StatusAbono() {
        return $this->belongsTo(new StatusAbono, 'id_status_abono', 'id_status_abono');
    }

    public function horario() {
        return $this->belongsTo(new AbonoHorario, 'id_abono', 'id_abono');
    }
}
