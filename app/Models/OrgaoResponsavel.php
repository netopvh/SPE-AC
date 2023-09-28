<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrgaoResponsavel extends Model
{
    protected $table = 'orgao_responsavel';

    protected $primaryKey = 'id_orgao_responsavel';
    
    const CREATED_AT = 'data_criacao_orgao_responsavel';
    const UPDATED_AT = 'data_atualizacao_orgao_responsavel';

    protected $fillable = [
        'id_orgao',
        'id_usuario',
        'funcao',
        'id_usuario_criacao_orgao_responsavel',
        'id_usuario_atualizacao_orgao_responsavel',
        'data_criacao_orgao_responsavel', 
        'data_atualizacao_orgao_responsavel'
    ];
	
	public function Usuario() {
        return $this->belongsTo(new Usuario, 'id_usuario', 'id_usuario');
    }
	
	public function Orgao() {
        return $this->belongsTo(new Orgao, 'id_orgao');
    }
}
