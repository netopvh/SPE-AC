<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Utils\Auth;

use App\Models\{ 
    Lotacao,
    LotacaoResponsavel,
    AfastamentoTemporarioLotacaoResponsavel 
};

class LotacaoResponsavelController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {

                $todos =[
                    'id_lotacao' => $args['id'],
                    'id_usuario' => ($request->getParams())['id_usuario'],
                    'id_usuario_criacao_lotacao_responsavel' => Auth::id_usuario(),
                    'id_usuario_atualizacao_lotacao_responsavel' => Auth::id_usuario(),
                ];

                $lotacao_responsavel = LotacaoResponsavel::where('id_lotacao', $todos['id_lotacao'])->where('status_lotacao_responsavel', 'A')->where('id_usuario', $todos['id_usuario'])->first();

                if($lotacao_responsavel){
                    return $response->withStatus(404)->withJson(['errorMessage' => 'Responsável já cadastrado nessa lotação!']);
                }

                $lotacao_responsavel = LotacaoResponsavel::create($todos);

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        $lotacao = Lotacao::find($args['id']);

        return $this->view(
            $response,
            'lotacoes',
            'responsaveis',
            [ 'lotacao' => $lotacao ]
        );
    }

    
    public function delete(Request $request, Response $response, $args)
    {
        $lotacao_responsavel = LotacaoResponsavel::find($args['id_lotacao_responsavel']);
        //$lotacao_responsavel->delete();

        $lotacao_responsavel->update([
            'status_lotacao_responsavel' => 'R'
        ]);

        return $response->withStatus(200)->withJson([]);
    }

    public function historicoChefia(Request $request, Response $response, $args)
    {

        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10; 

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;     
        
        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;

        $id_lotacao = ($request->getParams())['id_lotacao'] ? ($request->getParams())['id_lotacao'] : false;
        
        $lotacao_responsaveis = LotacaoResponsavel::join('usuario', 'usuario.id_usuario', 'lotacao_responsavel.id_usuario')
            ->where('status_lotacao_responsavel', 'R')
            ->join('lotacao', 'lotacao.id_lotacao', 'lotacao_responsavel.id_lotacao')
            ->where(function ($query) use ($id_lotacao) {
                if($id_lotacao){
                    $query->where('lotacao_responsavel.id_lotacao', $id_lotacao);
                }
            })
            ->where(function ($query) use ($search) {
                if($search){
                    $query->where('usuario.matricula_usuario', 'LIKE', $search)
                        ->orWhere('usuario.contrato_usuario', 'LIKE', $search)
                        ->orWhere('usuario.nome_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_comissao_usuario', 'LIKE', $search);
                }
            })->paginate($length, ['*'], 'page', $current_page)->toArray();

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $lotacao_responsaveis['to'],
            'iTotalDisplayRecords' => $lotacao_responsaveis['total'],
            'aaData' => $lotacao_responsaveis['data'],
        ];

        return $response->withStatus(200)->withJson( $resposta );

    }

    public function api_index(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10; 

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;     
        
        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;

        $id_lotacao = ($request->getParams())['id_lotacao'] ? ($request->getParams())['id_lotacao'] : false;
        
        $lotacao_responsaveis = LotacaoResponsavel::join('usuario', 'usuario.id_usuario', 'lotacao_responsavel.id_usuario')
            ->where('status_lotacao_responsavel', '!=', 'R')
            ->join('lotacao', 'lotacao.id_lotacao', 'lotacao_responsavel.id_lotacao')
            ->where(function ($query) use ($id_lotacao) {
                if($id_lotacao){
                    $query->where('lotacao_responsavel.id_lotacao', $id_lotacao);
                }
            })
            ->where(function ($query) use ($search) {
                if($search){
                    $query->where('usuario.matricula_usuario', 'LIKE', $search)
                        ->orWhere('usuario.contrato_usuario', 'LIKE', $search)
                        ->orWhere('usuario.nome_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_comissao_usuario', 'LIKE', $search);
                }
            })->paginate($length, ['*'], 'page', $current_page)->toArray();
			
		foreach( $lotacao_responsaveis['data'] as $chave => $dados ){
			$substituto = AfastamentoTemporarioLotacaoResponsavel::where('id_lotacao', $id_lotacao)
				->where('status', 'E')
				->where('id_substituto', $dados['id_usuario'])
				->first();
				
			$lotacao_responsaveis['data'][$chave]['substituto'] = $substituto ? true : false;
		}

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $lotacao_responsaveis['to'],
            'iTotalDisplayRecords' => $lotacao_responsaveis['total'],
            'aaData' => $lotacao_responsaveis['data'],
        ];

        return $response->withStatus(200)->withJson( $resposta );
    }

	public function afastamento_temporario(Request $request, Response $response, $args){
		if($request->getMethod() == 'POST'){
			DB::beginTransaction();
			try {
				$dados = ($request->getParams());
				$dados['id_usuario_cadastro'] = Auth::id_usuario();
				
				if($dados['data_inicial'] > $dados['data_final']){
					return $response->withStatus(404)->withJson( ['message' => 'A data final não pode ser menor que a data inicial'] );
					exit;
				}
				
				if($dados['id_usuario'] == $dados['id_substituto']){
					return $response->withStatus(404)->withJson( ['message' => 'Você não pode selecionar a mesma pessoa como substituta'] );
					exit;
				}
				
				//VERIFICA SE O USUÁRIO JÁ É RESPONSÁVEL
				$verifica = LotacaoResponsavel::where('id_lotacao', $dados['id_lotacao'])
						->where('id_usuario', $dados['id_substituto'])
						->where('status_lotacao_responsavel', 'A')
						->first();
				if( $verifica ){
					return $response->withStatus(404)->withJson( ['message' => 'Esse servidor já é responsavel por essa lotação'] );
					exit;
				}
				
				//VERIFICA SE O USUÁRIO JÁ ESTÁ AGENDADO PARA SER RESPONSÁVEL
				$verifica = AfastamentoTemporarioLotacaoResponsavel::where('id_lotacao', $dados['id_lotacao'])
						->where('status', '!=', 'R')
						->where(function ($query) use ($dados) {
							$query->where(function ($query) use ($dados) {
								$query->where('data_inicial', '<=', $dados['data_inicial'])
								->Where('data_final', '>=', $dados['data_inicial']);
							})
							->orWhere(function ($query) use ($dados) {
								$query->where('data_inicial', '<=', $dados['data_final'])
								->Where('data_final', '>=', $dados['data_final']);
							});
						})
						->first();
						
				if( $verifica ){
					return $response->withStatus(404)->withJson( ['message' => 'Já existe um agendamento para esse periodo'] );
					exit;
				}
				
				$lotacao_responsavel = AfastamentoTemporarioLotacaoResponsavel::create($dados);

                DB::commit();
                return $response->withStatus(200)->withJson([]);
				
			} catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
			
		} else if($request->getMethod() == 'PUT'){			
			$lotacao_responsavel = AfastamentoTemporarioLotacaoResponsavel::find($args['id']);
			
			$lotacao_responsavel->update([
				'id_usuario_atualizacao' => Auth::id_usuario(),
				'status' => 'R'
			]);

			return $response->withStatus(200)->withJson([]);
			
		} else {
			$dados = ($request->getParams());
			try {
				$afastamentos = AfastamentoTemporarioLotacaoResponsavel::with('Usuario')
								->with('Substituto')
								->where('id_usuario', $args['id'])
								->where('id_lotacao', $dados['lotacao'])
								->where('status', 'A')
								->get();
			} catch (\Throwable $th) {
				$afastamentos = [];
				$error = $th->getMessage();
			} 
			$resposta = [
				'dados' => $afastamentos,
				'id' => $dados,
			];

			return $response->withStatus(200)->withJson( $resposta );
		}
	}

}