<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\OrgaoResponsavel;

class Orgao extends Model
{
    protected $table = 'orgao';

    protected $primaryKey = 'id_orgao';

    public $incrementing = false;

    const CREATED_AT = 'data_criacao_orgao';
    const UPDATED_AT = 'data_atualizacao_orgao';

    protected $fillable = [
        'id_orgao',
        'descricao_orgao',
        'sigla_orgao',
        'mobile',
        'data_criacao_orgao',
        'data_atualizacao_orgao'
    ];

    public function lotacao()
    {
        return $this->hasMany(Lotacao::class, 'id_orgao', 'id_orgao');
    }

    public function OrgaoResponsavel()
    {
        return $this->hasMany(new OrgaoResponsavel, 'id_orgao', 'id_orgao')->where('funcao', 1)->with('Usuario');
    }
}
