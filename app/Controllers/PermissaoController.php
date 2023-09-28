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
    TipoPerfil,
    PerfilUsuario,
    Orgao,
    OrgaoResponsavel,
    LotacaoResponsavel
};

class PermissaoController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {


        return $this->view(
            $response,
            'permissoes',
            'index'
        );
    }
    
    public function store(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {

                $todos =[
                    'id_usuario' => ($request->getParams())['id_usuario'],
                    'id_tipo_perfil' => ($request->getParams())['id_tipo_perfil'],
                    'id_usuario_criacao_perfil_usuario' => Auth::id_usuario(),
                    'id_usuario_atualizacao_perfil_usuario' => Auth::id_usuario(),
                ];

                $perfil_usuario = PerfilUsuario::where('id_usuario', $todos['id_usuario'])->first();

                if($perfil_usuario){
                    return $response->withStatus(404)->withJson(['errorMessage' => 'Servidor já possui um perfil!']);
                }

                PerfilUsuario::create($todos);

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        $tipos_perfil = TipoPerfil::all()->toArray();

        return $this->view(
            $response,
            'permissoes',
            'store',
            [ 
                'tipos_perfil' => $tipos_perfil,
            ]
        );
    }

    public function delete(Request $request, Response $response, $args)
    {
        DB::beginTransaction();
        try {
            $perfil_usuario = PerfilUsuario::find($args['id']);
            OrgaoResponsavel::where('id_usuario', $perfil_usuario->id_usuario)->delete();
            LotacaoResponsavel::where('id_usuario', $perfil_usuario->id_usuario)->delete();
            $perfil_usuario->delete();

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

        $group_order = [
            0 => 'usuario.matricula_usuario',
            1 => 'usuario.contrato_usuario',
            2 => 'usuario.nome_usuario',
            4 => 'usuario.email_usuario',
            5 => 'tipo_perfil.id_tipo_perfil',
        ];

        if(!$order){
            $order['column'] = 2; 
            $order['dir'] = 'asc'; 
        }
        
        $usuarios = PerfilUsuario::join('usuario', 'usuario.id_usuario', 'perfil_usuario.id_usuario')
            ->join('tipo_perfil', 'tipo_perfil.id_tipo_perfil', 'perfil_usuario.id_tipo_perfil')
            ->join('orgao', 'usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
            ->join('lotacao', 'usuario.id_lotacao_exercicio_usuario', 'lotacao.id_lotacao')
            ->where('usuario.id_tipo_usuario', 1)
            ->where(function ($query) {
                if((Auth::perfil_usuario())['id_tipo_perfil'] == 2){
                    $query->where('tipo_perfil.id_tipo_perfil', 3)
                        ->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
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