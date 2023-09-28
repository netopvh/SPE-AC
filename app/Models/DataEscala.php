<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataEscala extends Model
{
    protected $table = 'data_escala';

    protected $primaryKey = 'id_data_escala';
    
    const CREATED_AT = 'data_criacao_data_escala';
    const UPDATED_AT = 'data_atualizacao_data_escala';

    protected $fillable = [
        'id_escala',        
        'data_escala',        
        'data_criacao_data_escala',
        'data_atualizacao_data_escala'
    ];
}
