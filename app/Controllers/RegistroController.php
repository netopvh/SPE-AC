<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Extensions\Support\Date;
use App\Extensions\Support\Recurrency;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Controllers\FolhaPontoController;

use App\Models\{
    Usuario,
    Ponto,
    Calendario,
    Ferias,
    Horario,
    Lotacao,
    Dispensa,
    Escala,
    DataEscala,
    Folha,
    Abono,
    Afastamento
};

use App\Utils\Auth;
use App\Classes\MongoDB;

class RegistroController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        if ($request->getMethod() == 'POST') {

            $request = $request->withQueryParams([
                'id_usuario' => Auth::id_usuario(),
                'ano' => $request->getQueryParam('ano'),
                'mes' => $request->getQueryParam('mes')
            ]);

            $request = $request->withAttribute('id_lotacao_servidor', $request->getQueryParam('id_lotacao'));


            $registros = new FolhaPontoController('');
            return $registros->show($request, $response, $args);

            return $response->withStatus(406)->withJson(['errorMessage' => 'Ocorreu uma falha ao assinar sua folha.']);
        }


        $usuario = Usuario::with('Orgao')->with('Lotacao')->with('TipoUsuario')->find(Auth::id_usuario())->toArray();

        $ano = $request->getQueryParam('ano') ? $request->getQueryParam('ano') : date('Y');
        $mes = $request->getQueryParam('mes') ? $request->getQueryParam('mes') : date('m');

        $data = Date::create($ano, $mes);

        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;

        $dia_mes_ano_inicial = ($data->startOfMonth())->toDateString();
        $dia_mes_ano_final = ($data->endOfMonth())->toDateString();

        $mongoDB = new MongoDB();
        $query = [
            '$or' => [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']],
            'data_ponto' => ['$gte' => $dia_mes_ano_inicial, '$lte' => $dia_mes_ano_final],
            'id_usuario' => (int) Auth::id_usuario()
        ];
        $lotacoes_ = $mongoDB->distinct('id_lotacao', $query);

        $lotacoes = [];

        foreach ($lotacoes_[0]->values as $id_lotacao_) {
            try {
                $lotacao = Lotacao::find($id_lotacao_);
                $lotacoes[] = $lotacao->toArray();
            } catch (\Throwable $th) {
            }
        }

        $folha = Folha::where('id_usuario', Auth::id_usuario())
            ->where('ano_folha', $request->getQueryParam('ano'))
            ->where('mes_folha', $request->getQueryParam('mes'))
            ->whereNotNull('id_lotacao')
            ->get();



        $minhaAssinatura = Folha::where('folha.id_usuario', Auth::id_usuario())
            ->where('ano_folha', $request->getQueryParam('ano'))
            ->where('mes_folha', $request->getQueryParam('mes'))
            ->whereNull('id_lotacao')
            ->first();

        // $dados = $this->registro($request, $response, $args, $id_lotacao);


        $request = $request->withQueryParams([
            'id_usuario' => Auth::id_usuario(),
            'ano' => $ano,
            'mes' => $mes,
            'validacao' => true,
            'id_lotacao' => $request->getQueryParam('id_lotacao')
        ]);

        $registros = new FolhaPontoController($this->container);
        $dados = $registros->registro($request, $response, $args, $id_lotacao);




        $anos = [];
        for ($i = 2019; $i <= date('Y'); $i++) {
            $anos[] = $i;
        }

        $dados['usuario'] = $usuario;
        $dados['years'] = $anos;
        $dados['lotacoes'] = $lotacoes;
        $dados['id_lotacao'] = $id_lotacao;
        $dados['folha'] = $folha ? $folha->toArray() : null;
        $dados['minhaAssinatura'] = $minhaAssinatura;
        $dados['minhaAssinaturaStatus'] = $minhaAssinatura ? 'S' : 'N';

        return $this->view(
            $response,
            'registros',
            'index',
            $dados
        );
    }

    public function print(Request $request, Response $response, $args)
    {
        $usuario = Usuario::with('Lotacao')->find(Auth::id_usuario())->toArray();

        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;

        $horario = Horario::where(function ($query) use ($usuario) {
            if ($usuario['id_horario']) {
                return $query->where('id_horario', $usuario['id_horario']);
            }
            return $query->leftJoin('horario_padrao', function ($query) use ($usuario) {
                $query->on('horario_padrao.id_horario', 'horario.id_horario')
                    ->where('horario_padrao.status', 'S')
                    ->where('horario_padrao.id_orgao', $usuario['id_orgao_exercicio_usuario']);
            });
        })->first();

        $usuario['entrada_1_horario'] = date('H:i', strtotime('2020-01-01 ' . $horario->entrada_1_horario));
        $usuario['saida_1_horario'] = date('H:i', strtotime('2020-01-01 ' . $horario->saida_1_horario));
        $usuario['entrada_2_horario'] = date('H:i', strtotime('2020-01-01 ' . $horario->entrada_2_horario));
        $usuario['saida_2_horario'] = date('H:i', strtotime('2020-01-01 ' . $horario->saida_2_horario));

        $folha = Folha::where('id_usuario', Auth::id_usuario())
            ->where('ano_folha', $request->getQueryParam('ano'))
            ->where('mes_folha', $request->getQueryParam('mes'))
            ->whereNotNull('id_lotacao')
            ->get();



        $minhaAssinatura = Folha::where('folha.id_usuario', Auth::id_usuario())
            ->where('ano_folha', $request->getQueryParam('ano'))
            ->where('mes_folha', $request->getQueryParam('mes'))
            ->whereNull('id_lotacao')
            ->first();

        $request = $request->withQueryParams([
            'id_usuario' => Auth::id_usuario(),
            'ano' => $request->getQueryParam('ano'),
            'mes' => $request->getQueryParam('mes'),
            'validacao' => true,
            'id_lotacao' => $request->getQueryParam('id_lotacao')
        ]);

        $registros = new FolhaPontoController('');
        $dados = $registros->registro($request, $response, $args, $id_lotacao);


        $horario = Horario::where(function ($query) use ($usuario, $dados) {
            if ($dados['ids_horario'][0]) {
                return $query->whereIn('id_horario', $dados['ids_horario']);
            } else if ($usuario['id_horario']) {
                return $query->where('id_horario', $usuario['id_horario']);
            }
            return $query->whereRaw("CASE 
                WHEN (
                    SELECT 
                        count(*) 
                    FROM 
                        horario_padrao 
                    WHERE 
                        horario_padrao.id_horario=horario.id_horario AND
                        horario_padrao.status='S' AND
                        horario_padrao.id_orgao=" . $usuario['id_orgao_exercicio_usuario'] . "
                ) > 0 THEN true
                ELSE false
            END");
        })->get();

        $dados['horarios'] = $horario;
        $dados['usuario'] = $usuario;
        $dados['folha'] = $folha ? $folha->toArray() : null;
        $dados['minhaAssinatura'] = $minhaAssinatura;
        $dados['lotacao_atual'] = $id_lotacao;

        return $this->view(
            $response,
            'print',
            'print',
            $dados
        );
    }

    public function registro(Request $request, Response $response, $args, $id_lotacao = null)
    {
        $pontos = [];

        $data = Date::create($request->getQueryParam('ano'), $request->getQueryParam('mes'));

        $recurrency = new Recurrency();
        $recurrency->startDate($data->startOfMonth())
            ->until($data->endOfMonth())
            ->freq('daily')
            ->generateOccurrences();

        $lotacao = null;

        if ($id_lotacao) {
            $lotacao_ = Lotacao::find($id_lotacao);
            if ($lotacao_) {
                $lotacao = $lotacao_->toArray();
            }
        }

        foreach ($recurrency->occurrences as $NewData) {
            if (! $NewData->isWeekend()) {

                $dataEnvio = $NewData->toDateString();

                $mongoDB = new MongoDB();
                $mongoDB->setFilter('finalidade_ponto', 'ABONO');
                $mongoDB->setFilter('data_ponto', new \MongoDB\BSON\Regex($dataEnvio));
                $mongoDB->setFilter('id_usuario', (int) Auth::id_usuario());

                if ($id_lotacao) {
                    $mongoDB->setFilter('id_lotacao', (int) $id_lotacao);
                }

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

                $afastamentos_ = Afastamento::where('data_inicio_afastamento', '<=', $dataEnvio)
                    ->where('data_fim_afastamento', '>=', $dataEnvio)
                    ->where('matricula_afastamento', Auth::matricula_usuario())
                    ->where('contrato_afastamento', Auth::contrato_usuario())
                    ->first();

                $dispensas_ = Dispensa::where('data_inicio_dispensa', '<=', $dataEnvio)
                    ->where('situacao_dispensa', 'S')
                    ->where(function ($query) use ($dataEnvio) {
                        $query->whereNull('data_fim_dispensa')->orWhere('data_fim_dispensa', '>=', $dataEnvio);
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

                $abono_indeferido = Abono::where(function ($query) use ($dataEnvio) {
                    $query->whereNull('data_final_abono')
                        ->where('data_abono', $dataEnvio)
                        ->orWhere('data_abono', '<=', $dataEnvio)->where('data_final_abono', '>=', $dataEnvio);
                })
                    ->where('id_status_abono', 4)
                    ->where('id_usuario_criacao_abono', Auth::id_usuario())
                    ->first();

                // $abono_aguardando = Abono::where(function ($query) use ($dataEnvio){
                //         $query->whereNull('data_final_abono')
                //             ->where('data_abono', $dataEnvio)
                //             ->orWhere('data_abono', '<=', $dataEnvio)->where('data_final_abono', '>=', $dataEnvio);
                //     })
                //     ->where('id_status_abono', 1)->orWhere('id_status_abono', 2)
                //     ->where('id_usuario_criacao_abono', Auth::id_usuario())
                //     ->first();

                $ferias = '';
                $afastamentos = '';
                $dispensas = '';
                $escalas = '';

                if ($ferias_) {
                    $ferias = 'férias';
                }

                if ($afastamentos_) {
                    $afastamentos = 'afastado/licenciado';
                }

                if ($escalas_) {
                    $escalas = $escalas_->amparo_legal_escala;
                }

                if ($dispensas_) {
                    $dispensas = $dispensas_->amparo_legal_dispensa;
                }

                $pontos[$NewData->format('d')] = [
                    'pontos' => $pontos_formatados,
                    'abonos' => $pontos_abonos_formatados,
                    'abono_indeferido' => $abono_indeferido,
                    // 'abono_aguardando' => $abono_aguardando,
                    'ferias' => $ferias,
                    'afastamentos' => $afastamentos,
                    'dispensas' => $dispensas,
                    'escalas' => $escalas,
                    'datas' => $datas,
                    'dia_nome' => Date::dayName($NewData->weekDay()),
                    'fim_de_semana' => false
                ];
            } else {
                $pontos[$NewData->format('d')] = [
                    'dia_nome' => Date::dayName($NewData->weekDay()),
                    'fim_de_semana' => true
                ];
            }
        }

        $anos = [];
        for ($i = 2019; $i <= date('Y'); $i++) {
            $anos[] = $i;
        }

        return [
            'pontos' => $pontos,
            'lotacao' => $lotacao,
            'years' => $anos,
            'ano' => $request->getQueryParam('ano'),
            'mes' => $request->getQueryParam('mes')
        ];

    }
}