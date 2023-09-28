<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Utils\Auth;

use App\Models\{ 
    Configuracao
};

class ConfiguracaoController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->view(
            $response,
            'configuracoes',
            'index'
        );
    }

    public function store(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {
                
                $todos = $request->getParams();
                $todos['id_usuario_criacao_configuracao'] = Auth::id_usuario();
                $todos['id_usuario_atualizacao_configuracao'] = Auth::id_usuario();

                Configuracao::create($todos);

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }
    }

    public function update(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {

                $configuracao = Configuracao::find($args['id']);

                $todos = [];
                $todos['valor_configuracao'] = ($request->getParams())['valor_configuracao'];
                $todos['id_usuario_atualizacao_configuracao'] = Auth::id_usuario();
 
                $configuracao->update($todos);

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }
    }

    public function api_index(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10; 

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;     
        
        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;

        $order = isset(($request->getParams())['order']) ? ($request->getParams())['order'][0] : null;

        $group_order = [
            0 => 'configuracao.chave_configuracao',
            1 => 'configuracao.valor_configuracao',
        ];

        if(!$order){
            $order['column'] = 0; 
            $order['dir'] = 'asc'; 
        }
        
        $configuracoes = Configuracao::where(function ($query) use ($search) {
                if($search){
                    $query->where('configuracao.chave_configuracao', 'LIKE', $search)
                        ->orWhere('configuracao.valor_configuracao', 'LIKE', $search);
                }
            })
            ->orderBy($group_order[$order['column']], $order['dir'])
            ->paginate($length, ['*'], 'page', $current_page)->toArray();

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $configuracoes['to'],
            'iTotalDisplayRecords' => $configuracoes['total'],
            'aaData' => $configuracoes['data'],
        ];

        return $response->withStatus(200)->withJson( $resposta );
    }
}