<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{
    Usuario,
    Orgao,
    Lotacao,
    Horario,
    StatusAbono
};

class AbonoHorario extends Model
{
    protected $table = 'abono_horario';

    protected $primaryKey = 'id_abono_horario';
    
    const CREATED_AT = NULL;
    const UPDATED_AT = NULL;

    protected $fillable = [  
        'id_abono',
        'entrada_1_horario',
        'saida_1_horario',
        'entrada_2_horario',
        'saida_2_horario'
    ];
}
