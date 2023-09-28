<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\{
    Orgao,
    TipoLotacao,
    LotacaoResponsavel
};

class Lotacao extends Model
{
    protected $table = 'lotacao';

    protected $primaryKey = 'id_lotacao';
    
    const CREATED_AT = 'data_criacao_lotacao';
    const UPDATED_AT = 'data_atualizacao_lotacao';

    protected $fillable = [
        'id_orgao',
        'descricao_lotacao',
        'sigla_lotacao',
        'municipio_lotacao',
        'data_criacao_lotacao', 
        'data_atualizacao_lotacao',
        'status_lotacao'
    ];

    public function Orgao() {
        return $this->belongsTo(new Orgao, 'id_orgao', 'id_orgao');
    }
	
    public function TipoLotacao() {
        return $this->belongsTo(new TipoLotacao, 'id_tipo_lotacao', 'id_tipo_lotacao');
    }

    public function LotacaoResponsavel() {
        return $this->hasMany(new LotacaoResponsavel, 'id_lotacao', 'id_lotacao')->where('status_lotacao_responsavel', 'A')->with('Usuario');
    }
}
