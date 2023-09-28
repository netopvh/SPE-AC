<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{
    Usuario,
    DataEscala
};

class Escala extends Model
{
    protected $table = 'escala';

    protected $primaryKey = 'id_escala';
    
    const CREATED_AT = 'data_criacao_escala';
    const UPDATED_AT = 'data_atualizacao_escala';

    protected $fillable = [
        'id_usuario',
        'amparo_legal_escala',
        'id_usuario_criacao_escala',
        'id_usuario_atualizacao_escala',        
        'data_criacao_escala',
        'data_atualizacao_escala'
    ];

    public function Usuario() {
        return $this->belongsTo(new Usuario, 'id_usuario', 'id_usuario');
    }

    public function DataEscala() {
        return $this->hasMany(new DataEscala, 'id_escala', 'id_escala')->orderBy('data_escala');
    }
}
