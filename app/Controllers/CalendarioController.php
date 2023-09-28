<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Extensions\Support\Date;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Utils\Auth;

use App\Models\{ 
    Usuario,
    Horario,
    Calendario
};

class CalendarioController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
		
		$anos = [];
		for($i=2019;$i<=date('Y');$i++){
			$anos[] = $i;
		}

        return $this->view(
            $response,
            'calendarios',
            'index',
            [
                'years' => $anos,
                'ano' => $request->getQueryParam('ano'),
                'mes' => $request->getQueryParam('mes')
            ]
        );
    }

    public function show(Request $request, Response $response, $args)
    {
        $usuario = Usuario::with('TipoUsuario')->with('Orgao')->with('Lotacao')->find($args['id'])->toArray();

        return $this->view(
            $response,
            'calendarios',
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
                    'data_calendario' => ($request->getParams())['data_calendario'],
                    'tipo_calendario' => ($request->getParams())['tipo_calendario'],
                    'descricao_calendario' => ($request->getParams())['descricao_calendario'],
                    'amparo_calendario' => ($request->getParams())['amparo_calendario'],
                    'id_usuario_criacao_calendario' => Auth::id_usuario(),
                    'id_usuario_atualizacao_calendario' => Auth::id_usuario(),
                ];

                $calendario = Calendario::create($todos);

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        return $this->view(
            $response,
            'calendarios',
            'store'
        );
    }

    public function update(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'POST'){
            DB::beginTransaction();
            try {

                $calendario = Calendario::find($args['id']);

                $calendario->update([
                    'data_calendario' => ($request->getParams())['data_calendario'],
                    'tipo_calendario' => ($request->getParams())['tipo_calendario'],
                    'descricao_calendario' => ($request->getParams())['descricao_calendario'],
                    'amparo_calendario' => ($request->getParams())['amparo_calendario'],
                    'id_usuario_atualizacao_calendario' => Auth::id_usuario(),
                ]);

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        $calendario = Calendario::find($args['id'])->toArray();

        return $this->view(
            $response,
            'calendarios',
            'update',
            [ 
                'calendario' => $calendario,
            ]
        );
    }

    public function destroy(Request $request, Response $response, $args)
    {
        if($request->getMethod() == 'DELETE'){
            DB::beginTransaction();
            try {

                $calendario = Calendario::find($args['id']);

                $calendario->delete();

                DB::commit();
                return $response->withStatus(200)->withJson([]);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }
    }

    public function api_index(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10; 

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;     
        
        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;

        $order = ($request->getParams())['order'][0];

        $group_order = [
            0 => 'calendario.data_calendario',
            1 => 'calendario.tipo_calendario',
            2 => 'calendario.descricao_calendario'
        ];

        $ano = $request->getQueryParam('ano');
        $mes = $request->getQueryParam('mes');
        
        $calendarios = Calendario::where(function ($query) use ($search) {
                if($search){
                    $query->where('calendario.data_calendario', 'LIKE', $search)
                        ->orWhere('calendario.tipo_calendario', 'LIKE', $search)
                        ->orWhere('calendario.descricao_calendario', 'LIKE', $search)
                        ->orWhere('calendario.amparo_calendario', 'LIKE', $search);
                }
            })
            ->where(function ($query) use ($ano, $mes){
                if($ano && $mes){
                    $data = Date::create($ano, $mes);
                    $dia_mes_ano_inicial = explode(' ',($data->startOfMonth())->toDateString());
                    $dia_mes_ano_final = explode(' ',($data->endOfMonth())->toDateString());

                    $query->where('calendario.data_calendario', '>=', $dia_mes_ano_inicial[0]) 
                        ->where('calendario.data_calendario', '<=', $dia_mes_ano_final[0]);
                }
            })
            ->orderBy($group_order[$order['column']], $order['dir'])
            ->paginate($length, ['*'], 'page', $current_page)->toArray();

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $calendarios['to'],
            'iTotalDisplayRecords' => $calendarios['total'],
            'aaData' => $calendarios['data'],
        ];

        return $response->withStatus(200)->withJson( $resposta );
    }
}