<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Extensions\Support\Env;
use App\Extensions\Support\Date;
use App\Extensions\Support\Recurrency;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Classes\MinhasLotacoes;

use App\Models\{
    Usuario,
    Calendario,
    Abono,
    OrgaoResponsavel,
    LotacaoResponsavel,
    Ferias,
    Dispensa,
    Escala,
    DataEscala
};

use App\Utils\Auth;
use App\Classes\MongoDB;

class DashboardController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        $usuario = Usuario::with('Lotacao')->find(Auth::id_usuario())->toArray();

        $dataData = Date::create(date('Y-m-d'));
        $primeiroDia = date('Y-m-d H:i:s', strtotime($dataData->startOfMonth()->toString()));
        $ultimoDia = date('Y-m-d H:i:s', strtotime($dataData->endOfMonth()->toString()));
		
		if( intval(Env::get('dia_fechamento_folha')) >= intval(date('d')) ){
			$primeiroDia = date('Y-m-d H:i:s', strtotime('-1 month', strtotime($primeiroDia)));
		}

        $semana = date('W');
        $segunda_feira = date('Y-m-d', strtotime(date('Y') . 'W' . $semana . '-1'));
        $sexta_feira = date('Y-m-d', strtotime(date('Y') . 'W' . $semana . '-5'));
		
		if((Auth::perfil_usuario())['id_tipo_perfil'] == 3){
			$Lotacoes = MinhasLotacoes::lotacoes();
			$MinhasLotacoes = [];
			
			foreach($Lotacoes as $dados){
				if( $dados['status_lotacao_responsavel'] == 'A' or ( $dados['data_criacao_lotacao_responsavel'] <= $ultimoDia AND $dados['data_atualizacao_lotacao_responsavel'] >= $primeiroDia ) ){
					$MinhasLotacoes[] = $dados['id_lotacao'];
				}
			}
		}

        $pontos = [];

        $data1 = Date::create($segunda_feira);
        $data2 = Date::create($sexta_feira);

        $recurrency = new Recurrency();
        $recurrency->startDate($data1)
            ->until($data2->endOfDay())
            ->freq("daily")
            ->generateOccurrences();

        foreach ($recurrency->occurrences as $NewData) {
            $dataEnvio = $NewData->toDateString();

            $mongoDB = new MongoDB();
            $mongoDB->setFilter('finalidade_ponto', 'ABONO');
            $mongoDB->setFilter('data_ponto', new \MongoDB\BSON\Regex($dataEnvio));
            $mongoDB->setFilter('id_usuario', (int) Auth::id_usuario());
            $mongoDB->setOptions('sort', ['tipo_ponto' => 1]);
            $ponto_abonado = $mongoDB->executeQuery();

            $mongoDB->setFilter('finalidade_ponto', 'PONTO');
            $ponto = $mongoDB->executeQuery();
            

            $ponto_tipo = [];
            $pontos_formatados = [];

            for ($i = 0; $i < count($ponto); $i++) {
                $ponto_tipo[$ponto[$i]->tipo_ponto] = date('H:i', strtotime($ponto[$i]->data_ponto));
            }

            for ($i = 1; $i <= 4; $i++) {
                $pontos_formatados[$i] = isset($ponto_tipo[$i]) ? $ponto_tipo[$i] : '';
            }

            $ponto_abono_tipo = [];
            $pontos_abonos_formatados = [];

            for ($i = 0; $i < count($ponto_abonado); $i++) {
                $ponto_abono_tipo[$ponto_abonado[$i]->tipo_ponto] = date('H:i', strtotime($ponto_abonado[$i]->data_ponto));
            }

            for ($i = 1; $i <= 4; $i++) {
                $pontos_abonos_formatados[$i] = isset($ponto_abono_tipo[$i]) ? $ponto_abono_tipo[$i] : '';
            }

            $datas = Calendario::where('data_calendario', $dataEnvio)->orderBy('data_calendario')->get()->toArray();
            $ferias_ = Ferias::where('data_inicio_ferias', '<=', $dataEnvio)
                    ->where('data_fim_ferias', '>=', $dataEnvio)
                    ->where('matricula_ferias', Auth::matricula_usuario())
                    ->where('contrato_ferias', Auth::contrato_usuario())
                    ->first();

            $dispensas_ = Dispensa::where('data_inicio_dispensa', '<=', $dataEnvio)
                ->where('situacao_dispensa', 'S')
                ->where(function ($query) use ($dataEnvio){
                    $query->whereNull('data_fim_dispensa')->orWhere('data_fim_dispensa','>=', $dataEnvio);
                })
                ->where('id_usuario', Auth::id_usuario())
                ->first();
            
            $escalas_ = Escala::where('id_usuario', Auth::id_usuario())
                ->whereIn('id_escala', function ($query) use ($dataEnvio) {
                    $query->select('data_escala.id_escala')
                            ->from(with(new DataEscala())->getTable())
                            ->whereRaw("data_escala.id_escala = escala.id_escala")
                            ->where('data_escala.data_escala', $dataEnvio)
                            ->groupBy('data_escala.id_data_escala');
                })->first();

            $ferias = '';
            $dispensas = '';
            $escalas = '';
            
            if($ferias_){
                $ferias = 'férias';
            }
            
            if($dispensas_){
                $dispensas = $dispensas_->amparo_legal_dispensa;
            }

            if($escalas_){
                $escalas = $escalas_->amparo_legal_escala;
            }

            $pontos[$NewData->format('d')] = [
                'pontos' => $pontos_formatados,
                'abonos' => $pontos_abonos_formatados,
                'ferias' => $ferias,
                'dispensas' => $dispensas,
                'escalas' => $escalas,
                'datas' => $datas,
                'dia_nome' => Date::dayName($NewData->weekDay()),
                'fim_de_semana' => false
            ];
        }

        $abonos = [];

        if(Auth::perfil_usuario()){

            $abonos = Abono::join('status_abono','abono.id_status_abono','status_abono.id_status_abono')
                ->join('usuario', 'usuario.id_usuario', 'abono.id_usuario_criacao_abono')
                ->join('orgao', 'usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
                ->join('lotacao', 'usuario.id_lotacao_exercicio_usuario', 'lotacao.id_lotacao')
                ->where(function ($query) use ($MinhasLotacoes) {
                    if((Auth::perfil_usuario())['id_tipo_perfil'] == 2 || (Auth::perfil_usuario())['id_tipo_perfil'] == 4 ){
                        $query->whereIn('orgao.id_orgao',  function ($query) {
                            $query->select('id_orgao')
                                ->from(with(new OrgaoResponsavel())->getTable())
                                ->where('id_usuario', Auth::id_usuario());
                        });
                    } else if((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
						$query->whereIn('lotacao.id_lotacao',  $MinhasLotacoes);
                        /*$query->whereIn('lotacao.id_lotacao',  function ($query) {
                            $query->select('id_lotacao')
                                ->from(with(new LotacaoResponsavel())->getTable())
                                ->where('id_usuario', Auth::id_usuario());
                        });*/
                    }
                })
				->where(function($query) use ($primeiroDia, $ultimoDia) {
					$query->where('abono.data_abono', '>=', $primeiroDia )
					->where('abono.data_abono', '<=', $ultimoDia );
				})
                ->where('abono.id_status_abono', '!=', 3)
                ->where('abono.id_status_abono', '!=', 4)
                ->where('abono.id_status_abono', '!=', 5)
                ->orderBy('status_abono.descricao_status_abono')
                ->orderBy('abono.data_abono')
                ->limit(5)->get()->toArray();
        }
            
        $abonos_servidor = Abono::join('status_abono','abono.id_status_abono','status_abono.id_status_abono')
            ->where('id_usuario_criacao_abono', Auth::id_usuario())
            ->orderBy('status_abono.descricao_status_abono')
            ->orderBy('abono.data_abono')
            ->limit(5)->get()->toArray();

        $ferias = [];

        if(Auth::perfil_usuario()){

            $ferias = Ferias::leftJoin('usuario', function($join){
                    $join->on('usuario.matricula_usuario', 'ferias.matricula_ferias');
                    $join->on('usuario.contrato_usuario', 'ferias.contrato_ferias');
                })
                ->leftJoin('orgao','usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
                ->leftJoin('lotacao','usuario.id_lotacao_exercicio_usuario', 'lotacao.id_lotacao')
                ->where('ferias.data_fim_ferias', '>=', date('Y-m-d'))
                ->where(function ($query) use ($MinhasLotacoes) {
                    if((Auth::perfil_usuario())['id_tipo_perfil'] == 2 || (Auth::perfil_usuario())['id_tipo_perfil'] == 4 ){
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
                ->orderBy('ferias.data_fim_ferias')
                ->limit(5)->get()->toArray();
        }
        
        $ferias_agendadas = Ferias::leftJoin('usuario', function($join){
                $join->on('usuario.matricula_usuario', 'ferias.matricula_ferias');
                $join->on('usuario.contrato_usuario', 'ferias.contrato_ferias');
            })
            ->where('usuario.id_usuario', Auth::id_usuario())
            ->where('ferias.data_fim_ferias', '>=', date('Y-m-d'))
            ->orderBy('ferias.data_fim_ferias')
            ->limit(5)->get()->toArray();

        return $this->view(
            $response,
            'dashboard',
            'index',
            [ 
                'pontos' => $pontos,
                'usuario' => $usuario,
                'abonos' => $abonos,
                'abonos_servidor' => $abonos_servidor,
                'ferias' => $ferias,
                'ferias_agendadas' => $ferias_agendadas,
            ]
        );
    }
}