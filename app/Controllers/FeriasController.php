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
    Ferias,
    OrgaoResponsavel,
    LotacaoResponsavel,
};

class FeriasController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->view(
            $response,
            'ferias',
            'index'
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

        $group_order = [
            0 => 'usuario.matricula_usuario',
            1 => 'usuario.contrato_usuario',
            2 => 'usuario.nome_usuario',
            3 => 'lotacao.descricao_lotacao',
            4 => 'ferias.data_inicio_ferias',
            5 => 'ferias.data_fim_ferias',
            6 => 'ferias.qtd_dias_ferias',
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

        $ferias = Ferias::query()->leftJoin('usuario', function ($join) {
            $join->on('usuario.matricula_usuario', 'ferias.matricula_ferias');
            //$join->on('usuario.contrato_usuario', 'ferias.contrato_ferias');
        })
            ->leftJoin('orgao', 'usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
            ->leftJoin('lotacao', 'usuario.id_lotacao_exercicio_usuario', 'lotacao.id_lotacao')
            ->where('ferias.data_fim_ferias', '>=', date('Y-m-d'))
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
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->where('usuario.matricula_usuario', 'LIKE', $search)
                        ->orWhere('usuario.contrato_usuario', 'LIKE', $search)
                        ->orWhere('usuario.nome_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_comissao_usuario', 'LIKE', $search)
                        ->orWhere('lotacao.descricao_lotacao', 'LIKE', $search)
                        ->orWhere('orgao.descricao_orgao', 'LIKE', $search)
                        ->orWhere('orgao.sigla_orgao', 'LIKE', $search);
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
            'iTotalRecords' => $ferias['to'],
            'iTotalDisplayRecords' => $ferias['total'],
            'aaData' => $ferias['data'],
        ];

        return $response->withStatus(200)->withJson($resposta);
    }
}
