<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Utils\Auth;

use App\Models\{ 
    Usuario,
    Horario,
    HorarioPadrao,
    Calendario,
    Orgao
};

class HorarioController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        if(Auth::perfil_usuario()['id_tipo_perfil'] == 1){
            $orgaos = Orgao::all(); 
        } else if(in_array((Auth::perfil_usuario())['id_tipo_perfil'], [2])){
            $orgaos = Orgao::join('orgao_responsavel', 'orgao_responsavel.id_orgao', '=', 'orgao.id_orgao')
                ->where('orgao_responsavel.id_usuario', Auth::id_usuario())
                ->get();
        }

        return $this->view(
            $response,
            'horarios',
            'index',
            [
                'orgaos' => $orgaos
            ]
        );
    }

    public function store(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {

                //VERIFICA SE JÁ EXISTE HORARIOS IGUAIS
                $igual = Horario::where('entrada_1_horario', ($request->getParams())['entrada_1_horario'] . ':00')
                ->where('saida_1_horario', ($request->getParams())['saida_1_horario'] . ':00')
                ->where('entrada_2_horario', ($request->getParams())['entrada_2_horario'] ? ($request->getParams())['entrada_2_horario'] . ':00' : null)
                ->where('saida_2_horario', ($request->getParams())['saida_2_horario'] ? ($request->getParams())['saida_2_horario'] . ':00' : null)
                ->where('situacao_horario', 'S')
                ->get()
                ->toArray();

                if(count($igual) > 0){
                    return $response->withStatus(405)->withJson(['errorMessage' => 'Horário já cadastrado']);
                }

                $todos =[
                    'entrada_1_horario' => ($request->getParams())['entrada_1_horario'] . ':00',
                    'saida_1_horario' => ($request->getParams())['saida_1_horario'] . ':00',
                    'entrada_2_horario' => ($request->getParams())['entrada_2_horario'] ? ($request->getParams())['entrada_2_horario'] . ':00' : null,
                    'saida_2_horario' => ($request->getParams())['saida_2_horario'] ? ($request->getParams())['saida_2_horario'] . ':00' : null,
                    'id_usuario_criacao_horario' => Auth::id_usuario(),
                    'id_usuario_atualizacao_horario' => Auth::id_usuario(),
                ];

                $horario = Horario::create($todos);

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        return $this->view(
            $response,
            'horarios',
            'store'
        );
    }

    public function update(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {

                $horario = Horario::find($args['id']);
                $todos = $request->getParams();
                $todos['id_usuario_atualizacao_horario'] = Auth::id_usuario();

                $horario->update($todos);

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        $horario = Horario::find($args['id'])->toArray();

        return $this->view(
            $response,
            'horarios',
            'update',
            [ 
                'horario' => $horario,
            ]
        );
    }

    public function padrao(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();

            try {
                //DESABILITA TODOS OS HOARIOS QUE ESTÃO ATIVOS
                HorarioPadrao::where('status', 'S')
                ->where('status', 'S')
                ->where('id_orgao', $request->getParams()['id_orgao'])
                ->update(['status' => 'N', 'id_usuario_alteracao' => Auth::id_usuario()]);

                
                $todos = $request->getParams();
                $todos['id_usuario_cadastro'] = Auth::id_usuario();
                $todos['id_usuario_alteracao'] = null;

                $horario = HorarioPadrao::create($todos);
                DB::commit();
                return $response->withStatus(200)->withJson([]);
            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        return $response->withStatus(404)->withJson(['errorMessage' => 'Ação inválida']);
    }

    public function delete(Request $request, Response $response, $args)
    {
        $horario = HorarioPadrao::where('id_horario', $args['id'])->where('status', 'S')->first();
        
        if($horario){
            return $response->withStatus(404)->withJson(['errorMessage' => 'Você não pode excluir o horário padrão.']);
        }

        $horario->update([
            'situacao_horario' => 'N',
            'id_usuario_atualizacao_horario' => Auth::id_usuario(),
        ]);

        return $response->withStatus(200)->withJson([]);
    }

    public function api_index(Request $request, Response $response, $args)
    {

        $length =  $request->getParams()['length'] ? $request->getParams()['length'] : 10;
        $currentPage = ceil(($request->getParams()['start'] + 1) / $length);

        $id_orgao = $request->getParams()['id_orgao'] ?? Auth::id_orgao_exercicio_usuario(); 
        
        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;

        $order = isset(($request->getParams())['order']) ? ($request->getParams())['order'][0] : null;

        $group_order = [
            0 => 'horario.entrada_1_horario',
            1 => 'horario.saida_1_horario',
            2 => 'horario.entrada_2_horario',
            3 => 'horario.saida_2_horario',
        ];

        if(!$order){
            $order['column'] = 0; 
            $order['dir'] = 'asc'; 
        }
        
        $horarios = Horario::where('situacao_horario', 'S')
            ->where(function ($query) use ($search) {
                if($search){
                    $query->where('entrada_1_horario', 'LIKE', $search)
                        ->orWhere('saida_1_horario', 'LIKE', $search)
                        ->orWhere('entrada_2_horario', 'LIKE', $search)
                        ->orWhere('saida_2_horario', 'LIKE', $search);
                }
            })
            ->leftJoin("horario_padrao", function($query) use ($id_orgao) {
                return $query->on("horario_padrao.id_horario", "horario.id_horario")
                ->where("horario_padrao.id_orgao", $id_orgao)
                ->where("horario_padrao.status", "S");
            })
            ->select([
                'horario.*',
                DB::Raw("
                (CASE WHEN horario_padrao.id_horario_padrao IS NOT NULL THEN 'S' ELSE 'N' END) as padrao_horario
                ")            
            ])
            ->orderBy($group_order[$order['column']], $order['dir'])
            ->orderBy('horario.id_horario', $order['dir'])
            ->paginate($length, ['*'], 'page', $currentPage)
            ->toArray();

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $horarios['to'],
            'iTotalDisplayRecords' => $horarios['total'],
            'aaData' => $horarios['data']
        ];

        return $response->withStatus(200)->withJson( $resposta );
    }

    public function corrigir(Request $request, Response $response, $args)
    {
        $horario = Horario::where('padrao_horario', 'S')->first();
        $orgaos = Orgao::all();

        foreach($orgaos as $orgao){
            HorarioPadrao::updateOrInsert([
                'id_orgao' => $orgao->id_orgao,
                'id_horario' => $horario->id_horario,
                'data_cadastro' => date('Y-m-d H:i:s'),
                'status' => 'S'
            ],
            [
                'id_usuario_cadastro' => $horario->id_usuario_atualizacao_horario ?? 149,
                'data_alteracao' => date('Y-m-d H:i:s'),
            ]);
        }

        echo 'migrado'; exit;
    }

}