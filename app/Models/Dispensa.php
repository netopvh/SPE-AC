<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Usuario;

class Dispensa extends Model
{
    protected $table = 'dispensa';

    protected $primaryKey = 'id_dispensa';
    
    const CREATED_AT = 'data_criacao_dispensa';
    const UPDATED_AT = 'data_atualizacao_dispensa';

    protected $fillable = [
        'id_usuario',
        'amparo_legal_dispensa',
        'data_inicio_dispensa',
        'data_fim_dispensa',
        'situacao_dispensa',
        'id_usuario_criacao_dispensa',
        'id_usuario_atualizacao_dispensa',        
        'data_criacao_dispensa',
        'data_atualizacao_dispensa'
    ];

    public function Usuario() {
        return $this->belongsTo(new Usuario, 'id_usuario', 'id_usuario');
    }
}
