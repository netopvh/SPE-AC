<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Extensions\Support\Hash;
use App\Extensions\Support\Date;
use App\Extensions\Support\Recurrency;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;

use App\Models\{
    Lotacao
};

use App\Utils\Auth;

class LotacaoController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->view(
            $response,
            'lotacoes',
            'index'
        );
    }

    public function remover(Request $request, Response $response, $args)
    {
        $lotacao = Lotacao::find($args['id']);

        DB::beginTransaction();
        try {
            $lotacao->update([
                'status_lotacao' => 'R'
            ]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
        }

        $resposta = [
            'id_lotacao' => $args['id'],
            'lotacao' => $lotacao
        ];

        return $response->withStatus(200)->withJson($resposta);
    }

    public function api_index(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10; 

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;     
        
        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;

        $order = isset(($request->getParams())['order']) ? ($request->getParams())['order'][0] : null;

        $group_order = [
            0 => 'lotacao.descricao_lotacao',
            1 => 'lotacao.descricao_lotacao',
            3 => 'lotacao.sigla_lotacao',
            4 => 'lotacao.municipio_lotacao',
        ];

        if(!$order){
            $order['column'] = 0; 
            $order['dir'] = 'asc'; 
        }
        
        $lotacoes = Lotacao::with('LotacaoResponsavel')
			->with('TipoLotacao')
            ->where('status_lotacao', '!=', 'R')
            ->where(function ($query) use ($id_orgao) {
                if($id_orgao){
                    $query->where('id_orgao', $id_orgao);
                }
            })->where(function ($query) use ($search) {
                if($search){
                    $query->where('descricao_lotacao', 'LIKE', $search)
                        ->orWhere('sigla_lotacao', 'LIKE', $search);
                }
            })
            ->orderBy($group_order[$order['column']], $order['dir'])
            ->paginate($length, ['*'], 'page', $current_page)->toArray();

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $lotacoes['to'],
            'iTotalDisplayRecords' => $lotacoes['total'],
            'aaData' => $lotacoes['data'],
        ];

        return $response->withStatus(200)->withJson( $resposta );
    }
}