<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Utils\Auth;

use App\Models\{ 
    Orgao,
    Horario,
    OrgaoResponsavel,
    Usuario
};

class OrgaoController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->view(
            $response,
            'orgaos',
            'index'
        );
    }

    public function show(Request $request, Response $response, $args)
    {
        $orgao = Orgao::with('OrgaoResponsavel')->find($args['id'])->toArray();


        $efetivo = Usuario::where('situacao_usuario', 'A')
            ->where('id_tipo_usuario', 1)   
            ->where('tipo_contrato_usuario', 'Efetivo')
            ->where('id_orgao_exercicio_usuario', $args['id'])
            ->count();

        $comissao = Usuario::where('situacao_usuario', 'A')
            ->where('id_tipo_usuario', 1)
            ->where('tipo_contrato_usuario', 'Comissão')
            ->where('id_orgao_exercicio_usuario', $args['id'])
            ->count();

        $efetivos = Usuario::where('situacao_usuario', 'A')
            ->where('id_tipo_usuario', 1)
            ->where('tipo_contrato_usuario', 'Efetivo')
            ->where('id_orgao_exercicio_usuario', $args['id'])
            ->count();

        $efetivo_comissao = Usuario::where('situacao_usuario', 'A')
            ->where('id_tipo_usuario', 1)
            ->where('tipo_contrato_usuario', 'Efetivo/Comissão')
            ->where('id_orgao_exercicio_usuario', $args['id'])
            ->count();

        $temporario = Usuario::where('situacao_usuario', 'A')
            ->where('id_tipo_usuario', 1)
            ->where('tipo_contrato_usuario', 'Temporário')
            ->where('id_orgao_exercicio_usuario', $args['id'])
            ->count();

        $contrato_clt = Usuario::where('situacao_usuario', 'A')
            ->where('id_tipo_usuario', 1)
            ->where('tipo_contrato_usuario', 'Contrato CLT')
            ->where('id_orgao_exercicio_usuario', $args['id'])
            ->count();



        $em_exercicio = Usuario::where('situacao_usuario', 'A')
            ->where('id_tipo_usuario', 1)
            ->where('situacao_funcional_usuario', 'Em Exercício')
            ->where('id_orgao_exercicio_usuario', $args['id'])
            ->count();

        $cedido = Usuario::where('situacao_usuario', 'A')
            ->where('id_tipo_usuario', 1)
            ->where('situacao_funcional_usuario', 'Cedido')
            ->where('id_orgao_exercicio_usuario', $args['id'])
            ->count();

        $afastado_licenciado = Usuario::where('situacao_usuario', 'A')
            ->where('id_tipo_usuario', 1)
            ->where('situacao_funcional_usuario', 'Afastado/Licenciado')
            ->where('id_orgao_exercicio_usuario', $args['id'])
            ->count();


        $servidores = Usuario::where('situacao_usuario', 'A')
            ->where('id_tipo_usuario', 1)
            ->where('id_orgao_exercicio_usuario', $args['id'])
            ->count();       

        $terceirizados = Usuario::where('situacao_usuario', 'A')
            ->where('id_tipo_usuario', 2)
            ->where('id_orgao_exercicio_usuario', $args['id'])
            ->count();

        $estagiarios = Usuario::where('situacao_usuario', 'A')
            ->where('id_tipo_usuario', 3)
            ->where('id_orgao_exercicio_usuario', $args['id'])
            ->count();

        return $this->view(
            $response,
            'orgaos',
            'show',
            [ 
                'orgao' => $orgao,
                'terceirizados' => $terceirizados,
                'estagiarios' => $estagiarios,
                'efetivo' => $efetivo,
                'comissao' => $comissao,
                'efetivo_comissao' => $efetivo_comissao,
                'temporario' => $temporario,
                'contrato_clt' => $contrato_clt,
                'em_exercicio' => $em_exercicio,
                'cedido' => $cedido,
                'afastado_licenciado' => $afastado_licenciado,
                'servidores' => $servidores,

            ]
        );
    }

    public function update(Request $request, Response $response)
    {

        DB::beginTransaction();

        $id_orgao = $request->getParsedBody()['id_orgao'];
        $id_horario_padrao_inicial = $request->getParsedBody()['id_horario_padrao_inicial'];
        $id_horario_padrao = $request->getParsedBody()['id_horario_padrao'];
        $mobile = $request->getParsedBody()['mobile'];

        $orgao = Orgao::find($id_orgao);

        if($orgao) {
            $orgao->mobile = $mobile;
            $orgao->save();

            if($id_horario_padrao_inicial != $id_horario_padrao){
                $horario = Horario::where('id_orgao', $id_orgao);
                $horario->id_horario = $id_horario_padrao;
                $horario->save();
            }

            DB::commit();
            return $response->withStatus(200)->withJson([]);
        }

        DB::rollBack();
        return $response->withStatus(404)->withJson(['errorMessage' => 'Órgão não encontrado']);
    }

    public function api_index(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10; 

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;     
        
        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;

        $order = ($request->getParams())['order'][0];

        $group_order = [
            0 => 'orgao.id_orgao',
            1 => 'orgao.descricao_orgao',
            2 => 'orgao.sigla_orgao',
        ];

        if(!$order){
            $order['column'] = 0; 
            $order['dir'] = 'asc'; 
        }
        
        $orgaos = Orgao::where(function ($query) {
                if((Auth::perfil_usuario())['id_tipo_perfil'] == 2 ){
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                }
            })
            ->where(function ($query) use ($search) {
                if($search){
                    $query->where('descricao_orgao', 'LIKE', $search)
                        ->orWhere('sigla_orgao', 'LIKE', $search);
                }
            })
            ->orderBy($group_order[$order['column']], $order['dir'])
            ->paginate($length, ['*'], 'page', $current_page)->toArray();

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $orgaos['to'],
            'iTotalDisplayRecords' => $orgaos['total'],
            'aaData' => $orgaos['data'],
        ];

        return $response->withStatus(200)->withJson( $resposta );
    }
}