<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{
    Orgao,
    Lotacao,
    Horario,
    PerfilUsuario
};

class Usuario extends Model
{
    protected $table = 'usuario';

    protected $primaryKey = 'id_usuario';

    public $incrementing = false;

    const CREATED_AT = 'data_criacao_usuario';
    const UPDATED_AT = 'data_atualizacao_usuario';

    protected $fillable = [
        'id_usuario',
        'id_tipo_usuario',
        'id_orgao_exercicio_usuario',
        'id_lotacao_exercicio_usuario',
        'id_horario',
        'matricula_usuario',
        'cpf_usuario',
        'contrato_usuario',
        'tipo_contrato_usuario',
        'nome_usuario',
        'situacao_funcional_usuario',
        'data_admissao_usuario',
        'cargo_usuario',
        'cargo_comissao_usuario',
        'email_usuario',
        'regime_usuario',
        'situacao_usuario',
        'data_criacao_usuario',
        'data_atualizacao_usuario'
    ];

    protected $appends = [
        'visualizar_orgaos',
    ];

    public function getVisualizarOrgaosAttribute()
    {
        return $this->hasMany(new OrgaoResponsavel, 'id_usuario')->where('funcao', 3)->pluck('id_orgao');
    }

    public function TipoUsuario()
    {
        return $this->belongsTo(new TipoUsuario, 'id_tipo_usuario', 'id_tipo_usuario');
    }

    public function PerfilUsuario()
    {
        return $this->belongsTo(new PerfilUsuario, 'id_usuario', 'id_usuario')->with('TipoPerfil');
    }

    public function Orgaos()
    {
        return $this->hasMany(new OrgaoResponsavel, 'id_usuario')->where('funcao', 3);
    }

    public function Orgao()
    {
        return $this->belongsTo(new Orgao, 'id_orgao_exercicio_usuario', 'id_orgao');
    }

    public function Lotacao()
    {
        return $this->belongsTo(new Lotacao, 'id_lotacao_exercicio_usuario', 'id_lotacao');
    }

    public function Horario()
    {
        return $this->belongsTo(new Horario, 'id_horario', 'id_horario');
    }
}
