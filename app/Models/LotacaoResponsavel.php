<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Usuario;

class LotacaoResponsavel extends Model
{
    protected $table = 'lotacao_responsavel';

    protected $primaryKey = 'id_lotacao_responsavel';
    
    const CREATED_AT = 'data_criacao_lotacao_responsavel';
    const UPDATED_AT = 'data_atualizacao_lotacao_responsavel';

    protected $fillable = [
        'id_lotacao',
        'id_usuario',
        'id_usuario_criacao_lotacao_responsavel',
        'id_usuario_atualizacao_lotacao_responsavel',
        'data_criacao_lotacao_responsavel', 
        'data_atualizacao_lotacao_responsavel',
        'status_lotacao_responsavel'
    ];

    public function Usuario() {
        return $this->belongsTo(new Usuario, 'id_usuario', 'id_usuario');
    }
}
