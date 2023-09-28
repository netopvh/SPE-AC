<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Utils\Auth;
use App\Classes\MinhasLotacoes;

use App\Models\{ 
    Dispensa,
    OrgaoResponsavel
};

class DispensaController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->view(
            $response,
            'dispensas',
            'index'
        );
    }

    public function store(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {
                $id_usuario = ($request->getParams())['id_usuario'];
                $data_inicio_dispensa = ($request->getParams())['data_inicio_dispensa'];
                $dispensa = Dispensa::where('id_usuario', $id_usuario)
                    ->where('situacao_dispensa', 'S')
                    ->where(function ($query) use ($data_inicio_dispensa){
                        $query->whereNull('data_fim_dispensa')->orWhere('data_fim_dispensa', '>=', $data_inicio_dispensa);
                    })
                    ->first();

                if($dispensa){
                    if(!$dispensa->data_fim_dispensa){
                        return $response->withStatus(404)->withJson(['errorMessage' => 'Usuário possui dispensa em aberto!']);
                    }else if($dispensa->data_inicio_dispensa > $data_inicio_dispensa){
                        return $response->withStatus(404)->withJson(['errorMessage' => 'Não é possível cadastrar dispensa anterior a existente!']);
                    }else if($dispensa->data_fim_dispensa > $data_inicio_dispensa){
                        return $response->withStatus(404)->withJson(['errorMessage' => 'Usuário possui dispesa cadastrada com essas datas!']);
                    }else{
                        return $response->withStatus(404)->withJson(['errorMessage' => 'Usuário possui dispensa em aberto!']);
                    }
                }

                $todos =[
                    'id_usuario' => ($request->getParams())['id_usuario'],
                    'amparo_legal_dispensa' => ($request->getParams())['amparo_legal_dispensa'],
                    'data_inicio_dispensa' => ($request->getParams())['data_inicio_dispensa'],
                    'data_fim_dispensa' => ($request->getParams())['data_fim_dispensa'] != '' ? ($request->getParams())['data_fim_dispensa'] : null,
                    'situacao_dispensa' => 'S',
                    'id_usuario_criacao_dispensa' => Auth::id_usuario(),
                    'id_usuario_atualizacao_dispensa' => Auth::id_usuario(),
                ];

                $dispensa = Dispensa::create($todos);

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
        DB::beginTransaction();
        try {
            $dispensa = Dispensa::find($args['id']);

            $dispensa->update([
                'amparo_legal_dispensa' => ($request->getParams())['amparo_legal_dispensa'],
                'data_inicio_dispensa' => ($request->getParams())['data_inicio_dispensa'],
                'data_fim_dispensa' => ($request->getParams())['data_fim_dispensa'] != '' ? ($request->getParams())['data_fim_dispensa'] : null,
                'id_usuario_atualizacao_dispensa' => Auth::id_usuario(),
            ]);

            DB::commit();
            return $response->withStatus(200)->withJson([]);

        } catch (\Throwable $th) {
            DB::rollBack();
            return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
        }
    }

    public function delete(Request $request, Response $response, $args)
    {
        DB::beginTransaction();
        try {
            $dispensa = Dispensa::find($args['id']);

            $dispensa->update([
                'situacao_dispensa' => 'N',
                'id_usuario_atualizacao_dispensa' => Auth::id_usuario(),
            ]);

            DB::commit();
            return $response->withStatus(200)->withJson([]);

        } catch (\Throwable $th) {
            DB::rollBack();
            return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
        }
    }

    public function api_index(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10; 

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;     
        
        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;
        $id_orgao = ($request->getParams())['id_orgao'] ?? false;

        $order = isset(($request->getParams())['order']) ? ($request->getParams())['order'][0] : null;
		
		
		if((Auth::perfil_usuario())['id_tipo_perfil'] <> 1){
			$Lotacoes = MinhasLotacoes::lotacoes();
			$MinhasLotacoes = [];
			
			$ultimoDiaMes = str_pad($ano, 4, '20', STR_PAD_LEFT)."-".str_pad($mes, 2, '0', STR_PAD_LEFT)."-31 23:59:59";
			$primeiroDiaMes = str_pad($ano, 4, '20', STR_PAD_LEFT)."-".str_pad($mes, 2, '0', STR_PAD_LEFT)."-01 00:00:00";
			
			foreach($Lotacoes as $dados){
				if( $dados['status_lotacao_responsavel'] == 'A' or ( $dados['data_criacao_lotacao_responsavel'] <= $ultimoDiaMes AND $dados['data_atualizacao_lotacao_responsavel'] >= $primeiroDiaMes ) ){
					$MinhasLotacoes[] = $dados['id_lotacao'];
				}
			}
		}

        $group_order = [
            0 => 'usuario.matricula_usuario',
            1 => 'usuario.contrato_usuario',
            2 => 'usuario.nome_usuario',
            4 => 'dispensa.amparo_legal_dispensa',
            5 => 'dispensa.data_inicio_dispensa',
        ];

        if(!$order){
            $order['column'] = 2; 
            $order['dir'] = 'asc'; 
        }
        
        $dispensas = Dispensa::select([
                'dispensa.*', 
                'usuario.*', 
                'usuario_criacao.nome_usuario AS nome_usuario_criacao',
                'usuario_atualizacao.nome_usuario AS nome_usuario_atualizacao',
            ])
            ->join('usuario', 'usuario.id_usuario', 'dispensa.id_usuario')
            ->join('usuario AS usuario_criacao', 'usuario_criacao.id_usuario', 'dispensa.id_usuario_criacao_dispensa')
            ->join('usuario AS usuario_atualizacao', 'usuario_atualizacao.id_usuario', 'dispensa.id_usuario_atualizacao_dispensa')
            ->where('dispensa.situacao_dispensa', 'S')
            ->where(function ($query) use ($search) {
                if($search){
                    $query->where('usuario.matricula_usuario', 'LIKE', $search)
                        ->orWhere('usuario.contrato_usuario', 'LIKE', $search)
                        ->orWhere('usuario.nome_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_comissao_usuario', 'LIKE', $search)
                        ->orWhere('dispensa.amparo_legal_dispensa', 'LIKE', $search);
                }
            })
			->where(function ($query) use ($MinhasLotacoes){                
				if(in_array((Auth::perfil_usuario())['id_tipo_perfil'], [2])){
                    // $query->where('usuario.id_orgao_exercicio_usuario',  Auth::id_orgao_exercicio_usuario());
                    $query->whereIn('usuario.id_orgao_exercicio_usuario',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                } else if(in_array((Auth::perfil_usuario())['id_tipo_perfil'], [3])){
					$query->whereIn('usuario.id_lotacao_exercicio_usuario',  $MinhasLotacoes);
				} 
            })
            ->where(function ($query) use ($id_orgao) {
                if($id_orgao){
                    $query->where('usuario.id_orgao_exercicio_usuario', $id_orgao);
                }
            })
            ->orderBy($group_order[$order['column']], $order['dir'])
            ->paginate($length, ['*'], 'page', $current_page)->toArray();

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $dispensas['to'],
            'iTotalDisplayRecords' => $dispensas['total'],
            'aaData' => $dispensas['data'],
        ];

        return $response->withStatus(200)->withJson( $resposta );
    }
}