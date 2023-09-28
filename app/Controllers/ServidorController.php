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
    Horario,
    OrgaoResponsavel,
    LotacaoResponsavel,
    Orgao
};

class ServidorController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {

        return $this->view(
            $response,
            'servidores',
            'index'
        );
    }

    public function show(Request $request, Response $response, $args)
    {
        $usuario = Usuario::with('TipoUsuario')->with('Orgao')->with('Lotacao')->with('Horario')->find($args['id'])->toArray();

        return $this->view(
            $response,
            'servidores',
            'show',
            [ 'usuario' => $usuario ]
        );
    }

    public function update(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {

                $usuario = Usuario::find($args['id']);
                
                $id_horario = ($request->getParams())['id_horario'];

                if($id_horario == 'null'){
                    $id_horario = null;
                }else{
                    $usuarios_ = Usuario::with('Horario')
                    ->where('matricula_usuario', $usuario->matricula_usuario)
                    ->where('contrato_usuario', '!=', $usuario->contrato_usuario)
                    ->where('situacao_usuario', 'A')
                    ->get()->toArray();

                    if($usuarios_){
                        foreach($usuarios_ as $usuario_ ){
                            if($usuario_['id_horario'] == $id_horario){
                                return $response->withStatus(404)->withJson(['errorMessage' => 'O servidor possui outro contrato vinculado a este horário.']);
                            }
                        }
                    }
                }

                $usuario->update([
                    'id_horario' => $id_horario,
                    'id_usuario_atualizacao_usuario' => Auth::id_usuario()
                ]);

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        $usuario = Usuario::with('TipoUsuario')->with('Orgao')->with('Lotacao')->find($args['id'])->toArray();
        $horarios = Horario::where('situacao_horario', 'S')->get()->toArray();

        return $this->view(
            $response,
            'servidores',
            'update',
            [ 
                'usuario' => $usuario,
                'horarios' => $horarios
            ]
        );
    }

    public function api_index(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10; 

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;     
        
        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;
        $horario_padrao = isset(($request->getParams())['horario_padrao']) ? ($request->getParams())['horario_padrao'] : false;
        $id_orgao = isset(($request->getParams())['id_orgao']) ? ($request->getParams())['id_orgao'] : false;

        $order = isset(($request->getParams())['order']) ? ($request->getParams())['order'][0] : null;

        $group_order = [
            0 => 'usuario.matricula_usuario',
            1 => 'usuario.contrato_usuario',
            2 => 'usuario.nome_usuario',
            5 => 'orgao.sigla_orgao',
            6 => 'lotacao.descricao_lotacao',
        ];
		
		if((Auth::perfil_usuario())['id_tipo_perfil'] == 3){
			$Lotacoes = MinhasLotacoes::lotacoes();
			$MinhasLotacoes = [];
			
			foreach($Lotacoes as $dados){
				if( $dados['status_lotacao_responsavel'] == 'A' ){
					$MinhasLotacoes[] = $dados['id_lotacao'];
				}
			}
		}

        if(!$order){
            $order['column'] = 2; 
            $order['dir'] = 'asc'; 
        }
        
        $usuarios = Usuario::join('orgao', 'usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
            ->join('lotacao', 'usuario.id_lotacao_exercicio_usuario', 'lotacao.id_lotacao')
            ->with('TipoUsuario')
            ->with('Horario')
            ->where('usuario.id_tipo_usuario', 1)
            ->where('usuario.situacao_usuario', 'A')
            ->where(function ($query) use ($MinhasLotacoes) {
                if((Auth::perfil_usuario())['id_tipo_perfil'] == 2){
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                }else if((Auth::perfil_usuario())['id_tipo_perfil'] == 3){
					$query->whereIn('lotacao.id_lotacao',  $MinhasLotacoes);
                    /*$query->whereIn('lotacao.id_lotacao',  function ($query) {
                        $query->select('id_lotacao')
                            ->from(with(new LotacaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });*/
                }
            })
            ->where(function ($query) use ($search) {
                if($search){
                    $query->where('usuario.matricula_usuario', 'LIKE', $search)
                        ->orWhere('usuario.contrato_usuario', 'LIKE', $search)
                        ->orWhere('usuario.nome_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_comissao_usuario', 'LIKE', $search)
                        ->orWhere('orgao.descricao_orgao', 'LIKE', $search)
                        ->orWhere('orgao.sigla_orgao', 'LIKE', $search)
                        ->orWhere('lotacao.descricao_lotacao', 'LIKE', $search)
                        ->orWhere('lotacao.sigla_lotacao', 'LIKE', $search);
                }
            })
            ->where(function ($query) use ($horario_padrao) {
                if($horario_padrao == 'true'){
                    $query->whereNull('usuario.id_horario');
                }else if($horario_padrao == 'outros'){
                    $query->whereNotNull('usuario.id_horario');
                }
            })
            ->where(function ($query) use ($id_orgao) {
                if($id_orgao){
                    $query->where('orgao.id_orgao', $id_orgao);
                }
            })
            ->orderBy($group_order[$order['column']], $order['dir'])
            ->paginate($length, ['*'], 'page', $current_page)->toArray();

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $usuarios['to'],
            'iTotalDisplayRecords' => $usuarios['total'],
            'aaData' => $usuarios['data'],
        ];

        return $response->withStatus(200)->withJson( $resposta );
    }
}