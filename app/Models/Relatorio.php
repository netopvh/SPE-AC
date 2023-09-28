<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Relatorio extends Model
{
    protected $table = 'relatorio';

    protected $primaryKey = 'id_relatorio';
    
    const CREATED_AT = 'data_criacao_relatorio';
    const UPDATED_AT = 'data_atualizacao_relatorio';

    protected $fillable = [  
        'descricao_relatorio',    
        'link_relatorio',
        'situacao_relatorio',    
        'data_criacao_relatorio', 
        'data_atualizacao_relatorio'
    ];
}
