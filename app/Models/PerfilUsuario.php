<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\TipoPerfil;

class PerfilUsuario extends Model
{
    protected $table = 'perfil_usuario';

    protected $primaryKey = 'id_perfil_usuario';
    
    const CREATED_AT = 'data_criacao_perfil_usuario';
    const UPDATED_AT = 'data_atualizacao_perfil_usuario';

    protected $fillable = [ 
        'id_usuario',  
        'id_tipo_perfil',  
        'id_usuario_criacao_perfil_usuario',  
        'id_usuario_atualizacao_perfil_usuario',  
        'data_criacao_perfil_usuario', 
        'data_atualizacao_perfil_usuario'
    ];

    public function TipoPerfil() {
        return $this->belongsTo(new TipoPerfil, 'id_tipo_perfil', 'id_tipo_perfil');
    }
}
