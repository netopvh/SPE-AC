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
	
	public function Subordinadas() {
        return $this->hasMany(new Hierarquia, 'id_lotacao', 'id_lotacao_subordinada')->with('DadosLotacao');
    }
	
	public function DadosLotacao() {
        return $this->belongsTo(new Lotacao, 'id_lotacao', 'id_lotacao');
    }
	
	public function LotacaoSubordinada() {
        return $this->belongsTo(new Lotacao, 'id_lotacao', 'id_lotacao_subordinada');
    }
	
	
}
