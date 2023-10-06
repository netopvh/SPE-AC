<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{
    Lotacao,
};

class Hierarquia extends Model
{
    protected $table = 'hierarquia';

    protected $primaryKey = 'id_hierarquia';

    const CREATED_AT = NULL;
    const UPDATED_AT = NULL;

    protected $fillable = [
        'id_lotacao',
        'nivel_pai',
        'id_lotacao_subordinada'
    ];

    public function subordinadas()
    {
        return $this->hasMany(Hierarquia::class, 'id_lotacao', 'id_lotacao_subordinada');
    }

    public function dadosLotacao()
    {
        return $this->hasMany(Lotacao::class, 'id_lotacao', 'id_lotacao');
    }

    public function lotacaoSubordinada()
    {
        return $this->belongsTo(Lotacao::class, 'id_lotacao_subordinada', 'id_lotacao');
    }
}
