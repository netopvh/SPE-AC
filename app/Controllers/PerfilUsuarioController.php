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
    OrgaoResponsavel,
    LotacaoResponsavel,
};

class PerfilUsuarioController extends Controller
{
    
    public function api_index(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10; 

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;     
        
        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;
        $id_tipo_perfil = ($request->getParams())['id_tipo_perfil'];
        
        $usuarios = Usuario::join('orgao', 'usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
            ->join('lotacao', 'usuario.id_lotacao_exercicio_usuario', 'lotacao.id_lotacao')
            ->join('perfil_usuario', 'perfil_usuario.id_usuario', 'usuario.id_usuario')
            ->where(function ($query) use ($id_tipo_perfil) {
                $query->where('perfil_usuario.id_tipo_perfil', $id_tipo_perfil)
                    ->orWhere('perfil_usuario.id_tipo_perfil', 1);
                    
                if($id_tipo_perfil == 3) {
                    $query->orWhere(function($query){
                        return $query->where('perfil_usuario.id_tipo_perfil', 2)
                        ->where('orgao.id_orgao', Auth::id_orgao_exercicio_usuario());
                    });
                    
                }
            })
            ->where('usuario.id_tipo_usuario', 1)
            ->where('usuario.situacao_usuario', 'A')
            ->where(function ($query) {
                if((Auth::perfil_usuario())['id_tipo_perfil'] == 2 ){
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                }else if((Auth::perfil_usuario())['id_tipo_perfil'] == 3){
                    $query->whereIn('lotacao.id_lotacao',  function ($query) {
                        $query->select('id_lotacao')
                            ->from(with(new LotacaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                }
            })
            ->where(function ($query) use ($search) {
                if($search){
                    $query->where('usuario.matricula_usuario', 'LIKE', $search)
                        ->orWhere('usuario.nome_usuario', 'LIKE', $search);
                }
            })
            ->orderBy('usuario.nome_usuario')
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