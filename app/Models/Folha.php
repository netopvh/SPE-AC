<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Usuario;

class Folha extends Model
{
    protected $table = 'folha';

    protected $primaryKey = 'id_folha';
    
    const CREATED_AT = 'data_criacao_folha';
    const UPDATED_AT = 'data_atualizacao_folha';

    protected $fillable = [
        'id_usuario',   
        'id_lotacao',   
        'nome_usuario_responsavel',   
        'cargo_usuario_responsavel',   
        'cargo_comissao_usuario_responsavel',   
        'descricao_lotacao_usuario_responsavel',   
        'ano_folha',       
        'mes_folha',       
        'token_folha',       
        'total_assinaturas',       
        'data_criacao_folha', 
        'data_atualizacao_folha'
    ];

    public function Usuario() {
        return $this->belongsTo(new Usuario, 'id_usuario', 'id_usuario');
    }
}
