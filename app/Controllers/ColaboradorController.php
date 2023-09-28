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
    TipoUsuario,
    Orgao,
    OrgaoResponsavel,
    Lotacao,
    Horario
};

class ColaboradorController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->view(
            $response,
            'colaboradores',
            'index'
        );
    }

    public function show(Request $request, Response $response, $args)
    {
        $usuario = Usuario::with('TipoUsuario')->with('Orgao')->with('Lotacao')->with('Horario')->find($args['id'])->toArray();

        return $this->view(
            $response,
            'colaboradores',
            'show',
            [ 'usuario' => $usuario ]
        );
    }

    public function store(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {

                $todos =[
                    'id_tipo_usuario' => ($request->getParams())['id_tipo_usuario'],
                    'id_orgao_exercicio_usuario' => ($request->getParams())['id_orgao_exercicio_usuario'],
                    'id_lotacao_exercicio_usuario' => ($request->getParams())['id_lotacao_exercicio_usuario'],
                    'id_horario' => ($request->getParams())['id_horario'],
                    'cpf_usuario' => preg_replace("/[^0-9]/", "", ($request->getParams())['cpf_usuario']),
                    'nome_usuario' => strtoupper(($request->getParams())['nome_usuario']),
                    'cargo_usuario' => strtoupper(($request->getParams())['cargo_usuario']),
                    'email_usuario' => strtolower(($request->getParams())['email_usuario']),
                    'id_usuario_criacao_usuario' => Auth::id_usuario(),
                    'id_usuario_atualizacao_usuario' => Auth::id_usuario(),
                ];

                if($todos['id_horario'] == 'null'){
                    $todos['id_horario'] = null;
                }

                $usuario = Usuario::create($todos);

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        $tipos_usuario = TipoUsuario::all()->toArray();
        $orgaos = Orgao::all()->toArray();
        $horarios = Horario::where('situacao_horario', 'S')->get()->toArray();

        return $this->view(
            $response,
            'colaboradores',
            'store',
            [ 
                'tipos_usuario' => $tipos_usuario,
                'orgaos' => $orgaos,
                'horarios' => $horarios
            ]
        );
    }

    public function update(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {

                $todos =[
                    'nome_usuario' => strtoupper(($request->getParams())['nome_usuario']),
                    'email_usuario' => strtoupper(($request->getParams())['email_usuario']),
                    'cargo_usuario' => strtoupper(($request->getParams())['cargo_usuario']),
                    'id_orgao_exercicio_usuario' => ($request->getParams())['id_orgao_exercicio_usuario'],
                    'id_lotacao_exercicio_usuario' => ($request->getParams())['id_lotacao_exercicio_usuario'],
                    'id_tipo_usuario' => ($request->getParams())['id_tipo_usuario'],
                    'id_horario' => ($request->getParams())['id_horario'],
                    'id_usuario_atualizacao_usuario' => Auth::id_usuario(),
                ];
                $usuario = Usuario::find($args['id']);

                if($todos['id_horario'] == 'null'){
                    $todos['id_horario'] = null;
                }

                $usuario->update($todos);

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        $usuario = Usuario::with('TipoUsuario')->with('Orgao')->with('Lotacao')->find($args['id'])->toArray();
        $tipos_usuario = TipoUsuario::all()->toArray();
        $lotacoes = Lotacao::where('id_orgao', $usuario['id_orgao_exercicio_usuario'])->get()->toArray();
        $horarios = Horario::where('situacao_horario', 'S')->get()->toArray();

        return $this->view(
            $response,
            'colaboradores',
            'update',
            [ 
                'usuario' => $usuario,
                'tipos_usuario' => $tipos_usuario,
                'lotacoes' => $lotacoes,
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

        $order = isset(($request->getParams())['order']) ? ($request->getParams())['order'][0] : null;

        $group_order = [
            0 => 'usuario.cpf_usuario',
            1 => 'usuario.nome_usuario',
            3 => 'usuario.cargo_usuario',
            4 => 'usuario.id_tipo_usuario',
        ];

        if(!$order){
            $order['column'] = 1; 
            $order['dir'] = 'asc'; 
        }
   
		
		if((Auth::perfil_usuario())['id_tipo_perfil'] == 3){
			$Lotacoes = MinhasLotacoes::lotacoes();
			$MinhasLotacoes = [];
			
			foreach($Lotacoes as $dados){
				if( $dados['status_lotacao_responsavel'] == 'A' ){
					$MinhasLotacoes[] = $dados['id_lotacao'];
				}
			}
		}
        
        $usuarios = Usuario::with('TipoUsuario')
            ->with('Horario')
            ->where('usuario.id_tipo_usuario', '!=', 1)
            ->where('usuario.situacao_usuario', 'A')
            ->where(function ($query) use ($MinhasLotacoes) {
                if((Auth::perfil_usuario())['id_tipo_perfil'] == 2){
                    $query->whereIn('usuario.id_orgao_exercicio_usuario',  function ($query) {
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
                    $query->where('usuario.nome_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_comissao_usuario', 'LIKE', $search)
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