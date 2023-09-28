<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Utils\Auth;

use App\Models\{ 
    Orgao,
    OrgaoResponsavel 
};

class OrgaoResponsavelController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {

                $todos =[
                    'id_orgao' => $args['id'],
                    'id_usuario' => ($request->getParams())['id_usuario'],
                    'funcao' => ($request->getParams())['funcao'],
                    'id_usuario_criacao_orgao_responsavel' => Auth::id_usuario(),
                    'id_usuario_atualizacao_orgao_responsavel' => Auth::id_usuario(),
                ];

                $orgao_responsavel = OrgaoResponsavel::where('id_orgao', $todos['id_orgao'])->where('id_usuario', $todos['id_usuario'])->first();

                if($orgao_responsavel){
                    return $response->withStatus(404)->withJson(['errorMessage' => 'Responsável já cadastrado nesse orgão!']);
                }

                $orgao_responsavel = OrgaoResponsavel::create($todos);

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        $orgao = Orgao::find($args['id']);

        return $this->view(
            $response,
            'orgaos',
            'responsaveis',
            [ 'orgao' => $orgao ]
        );
    }

    
    public function delete(Request $request, Response $response, $args)
    {
        $orgao_responsavel = OrgaoResponsavel::find($args['id_orgao_responsavel']);
        $orgao_responsavel->delete();


        return $response->withStatus(200)->withJson([]);
    }

    public function api_index(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10; 

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;     
        
        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;

        $id_orgao = ($request->getParams())['id_orgao'] ? ($request->getParams())['id_orgao'] : false;
        
        $orgao_responsaveis = OrgaoResponsavel::join('usuario', 'usuario.id_usuario', 'orgao_responsavel.id_usuario')
            ->join('orgao', 'orgao.id_orgao', 'orgao_responsavel.id_orgao')
            ->join('lotacao', 'lotacao.id_lotacao', 'usuario.id_lotacao_exercicio_usuario')
            ->where(function ($query) use ($id_orgao) {
                if($id_orgao){
                    $query->where('orgao_responsavel.id_orgao', $id_orgao);
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
            'iTotalRecords' => $orgao_responsaveis['to'],
            'iTotalDisplayRecords' => $orgao_responsaveis['total'],
            'aaData' => $orgao_responsaveis['data'],
        ];

        return $response->withStatus(200)->withJson( $resposta );
    }
}