<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Utils\Auth;
use App\Classes\MinhasLotacoes;

use App\Models\{ 
    Escala,
    DataEscala,
    OrgaoResponsavel
};

class EscalaController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
		
		$anos = [];
		for($i=2019;$i<=date('Y');$i++){
			$anos[] = $i;
		}

        return $this->view(
            $response,
            'escalas',
            'index',
            [
                'years' => $anos,
                'ano' => $request->getQueryParam('ano'),
                'mes' => $request->getQueryParam('mes')
            ]
        );
    }

    public function store(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {

                $datas_escala = ($request->getParams())['datas_escala'];
                
                $datas_escala = explode(',', $datas_escala);

                $todos =[
                    'id_usuario' => ($request->getParams())['id_usuario'],
                    'amparo_legal_escala' => ($request->getParams())['amparo_legal_escala'],
                    'id_usuario_criacao_escala' => Auth::id_usuario(),
                    'id_usuario_atualizacao_escala' => Auth::id_usuario(),
                ];

                $escala = Escala::create($todos);

                foreach ($datas_escala as $data_escala) {
                    DataEscala::create([
                        'id_escala' => $escala->id_escala,
                        'data_escala' => $data_escala,
                    ]);
                }

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        return $this->view(
            $response,
            'escalas',
            'store'
        );

    }

    public function update(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {
                $escala = Escala::find($args['id']);

                DataEscala::where('id_escala', $escala->id_escala)->delete();

                $datas_escala = ($request->getParams())['datas_escala'];
                
                $datas_escala = explode(',', $datas_escala);

                foreach ($datas_escala as $data_escala) {
                    DataEscala::create([
                        'id_escala' => $escala->id_escala,
                        'data_escala' => $data_escala,
                    ]);
                }


                $escala->update([
                    'amparo_legal_escala' => ($request->getParams())['amparo_legal_escala'],
                    'id_usuario_atualizacao_escala' => Auth::id_usuario(),
                ]);

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        $escala = Escala::with('Usuario')->with('DataEscala')->find($args['id'])->toArray();

        return $this->view(
            $response,
            'escalas',
            'update',
            [
                'escala' => $escala
            ]
        );
    }

    public function delete(Request $request, Response $response, $args)
    {
        DB::beginTransaction();
        try {
            $escala = Escala::find($args['id']);

            $escala->delete();

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
        $ano = isset(($request->getParams())['ano']) ? ($request->getParams())['ano'] : false;
        $mes = isset(($request->getParams())['mes']) ? ($request->getParams())['mes'] : false;

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
        ];

        if(!$order){
            $order['column'] = 2; 
            $order['dir'] = 'asc'; 
        }
        
        $escalas = Escala::join('usuario', 'usuario.id_usuario', 'escala.id_usuario')
            ->with('DataEscala')
            ->whereIn('escala.id_escala', function ($query) use ($ano, $mes) {
                $query->select('data_escala.id_escala')
                        ->from(with(new DataEscala())->getTable())
                        ->whereRaw("data_escala.id_escala = escala.id_escala")
                        ->whereRaw("YEAR(data_escala.data_escala) = $ano")
                        ->whereRaw("MONTH(data_escala.data_escala) = $mes")
                        ->groupBy('data_escala.id_data_escala');
            })
            ->where(function ($query) use ($search) {
                if($search){
                    $query->where('usuario.matricula_usuario', 'LIKE', $search)
                        ->orWhere('usuario.contrato_usuario', 'LIKE', $search)
                        ->orWhere('usuario.nome_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_usuario', 'LIKE', $search)
                        ->orWhere('usuario.cargo_comissao_usuario', 'LIKE', $search);
                }
            })
			->where(function ($query) use ($MinhasLotacoes){
				if(in_array((Auth::perfil_usuario())['id_tipo_perfil'], [2])){
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
            'iTotalRecords' => $escalas['to'],
            'iTotalDisplayRecords' => $escalas['total'],
            'aaData' => $escalas['data'],
        ];

        return $response->withStatus(200)->withJson( $resposta );
    }
}