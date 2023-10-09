<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Utils\Auth;
use App\Classes\MinhasLotacoes;

use App\Models\{
    Usuario,
    Afastamento,
    OrgaoResponsavel,
    LotacaoResponsavel,
};

class AfastamentoController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        $descricoes = Afastamento::select('descricao_afastamento')->distinct('descricao_afastamento')->get()->toArray();

        return $this->view(
            $response,
            'afastamentos',
            'index',
            [
                'descricoes' => $descricoes
            ]
        );
    }

    public function api_index(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;

        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;
        $id_orgao = ($request->getParams())['id_orgao'] ?? false;

        $order = ($request->getParams())['order'][0];

        $descricao_afastamento = isset(($request->getParams())['descricao_afastamento']) ? ($request->getParams())['descricao_afastamento'] : false;

        $group_order = [
            0 => 'usuario.matricula_usuario',
            1 => 'usuario.contrato_usuario',
            2 => 'usuario.nome_usuario',
            3 => 'lotacao.descricao_lotacao',
            4 => 'afastamento.descricao_afastamento',
            5 => 'afastamento.data_inicio_afastamento',
            6 => 'afastamento.data_fim_afastamento',
            7 => 'afastamento.qtd_dias_afastamento',
        ];

        if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
            $Lotacoes = MinhasLotacoes::lotacoes();
            $MinhasLotacoes = [];

            foreach ($Lotacoes as $dados) {
                if ($dados['status_lotacao_responsavel'] == 'A') {
                    $MinhasLotacoes[] = $dados['id_lotacao'];
                }
            }
        }

        $afastamentos = Afastamento::leftJoin('usuario', function ($join) {
            $join->on('usuario.matricula_usuario', 'afastamento.matricula_afastamento');
            //$join->on('usuario.contrato_usuario', 'afastamento.contrato_afastamento');
        })
            ->leftJoin('orgao', 'usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
            ->leftJoin('lotacao', 'usuario.id_lotacao_exercicio_usuario', 'lotacao.id_lotacao')
            //->where('afastamento.data_fim_afastamento', '>=', date('Y-m-d'))
            ->with('Usuario.Lotacao')
            ->where(function ($query) use ($MinhasLotacoes) {
                if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2) {
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                } else if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
                    $query->whereIn('lotacao.id_lotacao',  $MinhasLotacoes);
                    /*$query->whereIn('lotacao.id_lotacao',  function ($query) {
                        $query->select('id_lotacao')
                            ->from(with(new LotacaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });*/
                }
            })
            ->where(function ($query) use ($descricao_afastamento) {
                if ($descricao_afastamento) {
                    $query->where('afastamento.descricao_afastamento', $descricao_afastamento);
                }
            })
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->where('usuario.matricula_usuario', 'LIKE', $search)
                        ->orWhere('usuario.contrato_usuario', 'LIKE', $search)
                        ->orWhere('usuario.nome_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_comissao_usuario', 'LIKE', $search)
                        ->orWhere('lotacao.descricao_lotacao', 'LIKE', $search)
                        ->orWhere('orgao.descricao_orgao', 'LIKE', $search)
                        ->orWhere('orgao.sigla_orgao', 'LIKE', $search)
                        ->orWhere('afastamento.descricao_afastamento', 'LIKE', $search);
                }
            })
            ->where(function ($query) use ($id_orgao) {
                if ($id_orgao) {
                    $query->where('orgao.id_orgao', $id_orgao);
                }
            })
            ->orderBy($group_order[$order['column']], $order['dir'])
            ->paginate($length, ['*'], 'page', $current_page)->toArray();

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $afastamentos['to'],
            'iTotalDisplayRecords' => $afastamentos['total'],
            'aaData' => $afastamentos['data'],
        ];

        return $response->withStatus(200)->withJson($resposta);
    }
}
