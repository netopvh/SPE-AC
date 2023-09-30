<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Models\Orgao;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Utils\Auth;

use App\Models\{
    Importacao,
};
use App\Classes\Turmalina;
use App\Services\ImportacaoService;

class ImportacaoController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        if ($request->getMethod() == 'POST') {
            try {

                $todos = [
                    'id_orgao' => ($request->getParams())['id_orgao'],
                ];

                $importacao = Importacao::where('situacao_importacao', 'S')->first();

                if ($importacao) {
                    return $response->withStatus(404)->withJson(['errorMessage' => 'Exite uma importação em andamento.']);
                }

                $idOrgao = $todos['id_orgao'] != 'all' ? $todos['id_orgao'] : 0;

                $service = new ImportacaoService();
                $service->setDsn('folha')->importar($idOrgao);

                return $response->withStatus(200)->withJson([]);
            } catch (\Throwable $th) {
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        return $this->view(
            $response,
            'importacao',
            'index'
        );
    }

    public function verificar(Request $request, Response $response, $args)
    {
        $importacao = Importacao::where('situacao_importacao', 'S')->first();

        if ($importacao) {
            return $response->withStatus(404)->withJson(['errorMessage' => 'Exite uma importação em andamento.']);
        }

        return $response->withStatus(200)->withJson([]);
    }

    public function api_index(Request $request, Response $response, $args)
    {
        $valid_lenght = ($request->getParams())['length'] > 0 ? ($request->getParams())['length'] : 10;

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] > 0 ? ($request->getParams())['length'] : 10;

        $search = ($request->getParams())['search'] ? '%' . strtoupper(($request->getParams())['search']) . '%' : false;

        try {
            if (TURMALINA) {
                $turmalina = new Turmalina();

                $query = "";

                if ($search) {
                    $query = " (NOME_ORGAO LIKE '$search' OR SIGLA_ORGAO LIKE '$search' ) AND ";
                }

                $orgaos = $turmalina->getOrgaos($query, $current_page);
            } else {
                $orgaos = Orgao::query()->select('id_orgao', 'descricao_orgao', 'sigla_orgao')
                    ->when($search, function ($query, $search) {
                        return $query->where('descricao_orgao', 'like', $search)
                            ->orWhere('sigla_orgao', 'like', $search);
                    })
                    ->paginate($length, ['*'], 'page', $current_page);
            }

            $resposta = [
                'draw' => (int) ($request->getParams())['draw'],
                'iTotalRecords' => TURMALINA ? $orgaos['total'] : $orgaos->total(),
                'iTotalDisplayRecords' => TURMALINA ? $orgaos['total'] : $orgaos->total(),
                'aaData' => TURMALINA ? $orgaos['data'] : $orgaos->items(),
            ];

            return $response->withStatus(200)->withJson($resposta);
        } catch (\Throwable $th) {
            return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
        }


    }
}