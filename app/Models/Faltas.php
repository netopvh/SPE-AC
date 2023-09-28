<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faltas extends Model
{
    protected $table = 'faltas';

    protected $primaryKey = 'id_falta';
    
    const CREATED_AT = NULL;
    const UPDATED_AT = NULL;

    protected $fillable = [
        'tipo',
        'qtd_dias',
        'matricula_usuario',
        'data_inicio',
        'data_termino'
    ];
}
