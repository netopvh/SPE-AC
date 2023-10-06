<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Extensions\Support\Env;
use App\Extensions\Support\Date;
use App\Extensions\Support\Recurrency;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Utils\Auth;
use App\Classes\MongoDB;
use App\Classes\MinhasLotacoes;

use App\Models\{
    Usuario,
    Horario,
    HorarioPadrao,
    Abono,
    AbonoHorario,
    PerfilUsuario,
    Lotacao,
    OrgaoResponsavel,
    LotacaoResponsavel,
    StatusAbono,
    Folha
};

class AbonoController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        $status_abono = StatusAbono::where('id_status_abono', '!=', 3)->get()->toArray();
        $anos = [];
        for ($i = 2019; $i <= date('Y'); $i++) {
            $anos[] = $i;
        }

        return $this->view(
            $response,
            'abonos',
            'index',
            [
                'status_abono' => $status_abono,
                'years' => $anos,
                'ano' => $request->getQueryParam('ano') ? $request->getQueryParam('ano') : date('Y'),
                'mes' => $request->getQueryParam('mes') ? $request->getQueryParam('mes') : date('m')
            ]
        );
    }

    public function index_servidor(Request $request, Response $response, $args)
    {
        $status_abono = StatusAbono::all()->toArray();
        $anos = [];
        for ($i = 2019; $i <= date('Y'); $i++) {
            $anos[] = $i;
        }

        $ano = $request->getQueryParam('ano') !== '' ? $request->getQueryParam('ano') : date('Y');
        $mes = $request->getQueryParam('mes') !== '' ? $request->getQueryParam('mes') : date('m');

        return $this->view(
            $response,
            'abonos/servidor',
            'index',
            [
                'status_abono' => $status_abono,
                'years' => $anos,
                'ano' => $ano,
                'mes' => $mes
            ]
        );
    }

    public function show(Request $request, Response $response, $args)
    {
        $perfil_usuario = PerfilUsuario::where('id_usuario', Auth::id_usuario())->first();

        $abono = Abono::select([
            '*',
            'abono.*',
            'usuario.*',
            'tipo_usuario.descricao_tipo_usuario',
            'responsavel.nome_usuario AS nome_responsavel',
            'lotacao.descricao_lotacao'
        ])
            ->join('usuario', 'abono.id_usuario_criacao_abono', 'usuario.id_usuario')
            ->join('tipo_usuario', 'usuario.id_tipo_usuario', 'tipo_usuario.id_tipo_usuario')
            ->join('lotacao', 'abono.id_lotacao', 'lotacao.id_lotacao')
            ->with('StatusAbono')
            ->with('horario')
            ->leftJoin('usuario AS responsavel', 'abono.id_usuario_responsavel', 'responsavel.id_usuario')
            ->find($args['id']);


        /* VERIFICAR TEM PERMISSÃO PARA DEFIRIR/INDEFERIR */
        $autorizadoAssinar = true;
        if (Auth::perfil_usuario()['id_tipo_perfil'] == 2) {
            if (in_array($abono->id_orgao, Auth::visualizar_orgaos())) {
                $autorizadoAssinar = false;
                $minhasLotacoes = MinhasLotacoes::lotacoesArray();
                if (in_array($abono->id_lotacao, $minhasLotacoes)) {
                    $autorizadoAssinar = true;
                }
            }
        }

        return $this->view(
            $response,
            'abonos',
            'show',
            [
                'abono' => $abono,
                'perfil_usuario' => $perfil_usuario,
                'autorizadoAssinar' => $autorizadoAssinar
            ]
        );
    }

    public function store(Request $request, Response $response, $args)
    {
        if ($request->getMethod() == 'POST') {
            DB::beginTransaction();
            try {
                if (($request->getParams())['tipo_documento']) {
                    if (($request->getParams())['tipo_documento'] == 'ATESTADO MÉDICO' or ($request->getUploadedFiles())['arquivo_documento']->getError() === UPLOAD_ERR_OK) {
                        $file = ($request->getUploadedFiles())['arquivo_documento'];

                        $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                        $file_name = $file->getClientFilename();
                        $name = uniqid(date('HisYmd'));

                        $file_directory = "uploads/documento/{$name}.{$extension}";

                        $file->moveTo(APP_DIR . $file_directory);
                    } else {
                        $file_directory = null;
                    }
                }

                if (
                    date('Y-m-d', strtotime(($request->getParams())['data_abono'])) > date('Y-m-d') ||
                    (
                        ($request->getParams())['data_final_abono'] &&
                        date('Y-m-d', strtotime(($request->getParams())['data_final_abono'])) > date('Y-m-d')
                    )
                ) {
                    return $response->withStatus(404)->withJson(['errorMessage' => 'Não é possível solicitar abonos futuro.']);
                }


                if (
                    ($request->getParams())['data_final_abono'] && date('Y-m', strtotime(($request->getParams())['data_abono'])) != date('Y-m', strtotime(($request->getParams())['data_final_abono']))
                ) {
                    return $response->withStatus(404)->withJson(['errorMessage' => 'O periodo do abono não pode ultrapassar o última dia do mês.']);
                }

                $todos = [
                    'id_lotacao' => ($request->getParams())['id_lotacao'],
                    'data_abono' => ($request->getParams())['data_abono'],
                    'motivo_abono' => ($request->getParams())['motivo_abono'],
                    'periodo_abono' => ($request->getParams())['periodo_abono'] ?? null,
                    'tipo_documento' => $file_directory ?? null,
                    'id_usuario_criacao_abono' => Auth::id_usuario(),
                    'id_usuario_atualizacao_abono' => Auth::id_usuario(),
                ];

                $servidor = Usuario::with('Horario')->find($todos[id_usuario_criacao_abono]);


                if ($servidor->horario->entrada_1_horario) {
                    $entrada_1_horario = $servidor->horario->entrada_1_horario;
                    $saida_1_horario = $servidor->horario->saida_1_horario;
                    $entrada_2_horario = $servidor->horario->entrada_2_horario;
                    $saida_2_horario = $servidor->horario->saida_2_horario;
                } else {
                    $horario_padrao = HorarioPadrao::where('horario_padrao.status', '=', 'S')->where('horario_padrao.id_orgao', $servidor->id_orgao_exercicio_usuario)->join('horario', 'horario.id_horario', 'horario_padrao.id_horario')->first();
                    $entrada_1_horario = $horario_padrao['entrada_1_horario'];
                    $saida_1_horario = $horario_padrao['saida_1_horario'];
                    $entrada_2_horario = $horario_padrao['entrada_2_horario'];
                    $saida_2_horario = $horario_padrao['saida_2_horario'];
                }


                //VERIFICA SE O HORARIO É COMPATIVEL
                if ($todos['periodo_abono'] == 'M') {
                    if ($entrada_1_horario > 12 and ($entrada_2_horario > 12 or $entrada_2_horario == NULL)) {
                        return $response->withStatus(404)->withJson(['errorMessage' => 'Seu horario de entrada é apenas no período vespertino. Caso seu horario tenha alterado recentemente, solicite ao seu superior a alteração no ponto']);
                    }
                }
                if ($todos['periodo_abono'] == 'V') {
                    if ($entrada_1_horario < 12 and ($entrada_2_horario < 12 or $entrada_2_horario == NULL)) {
                        return $response->withStatus(404)->withJson(['errorMessage' => "Seu horario de entrada é apenas no período matutino. Caso seu horario tenha alterado recentemente, solicite ao seu superior a alteração no ponto"]);
                    }
                }

                //PEGA O ID DA LOTAÇÃO DO ÚLTIMO DIA ANTES DO ABONO
                $id_lotacao_temp = NULL;
                for ($i = 0; $i <= 15; $i++) {
                    $dataVer = date('Y-m-d', strtotime("-$i days", strtotime($todos['data_abono'])));
                    $mongoDB = new MongoDB();
                    $mongoDB->setFilter('data_ponto', ['$gte' => "$dataVer 00:00:00", '$lte' => "$dataVer 23:59:59"]);
                    $mongoDB->setFilter('id_usuario', (int) $todos['id_usuario_criacao_abono']);
                    $dataCheck = $mongoDB->executeQuery();

                    foreach ($dataCheck as $dados) {
                        if ($dados->id_lotacao) {
                            $id_lotacao_temp = $dados->id_lotacao;
                            break;
                        }
                    }

                    if ($id_lotacao_temp) {
                        $todos['id_lotacao'] = $id_lotacao_temp;
                        break;
                    }
                }
                //FIM

                $folha = Folha::where('id_usuario', Auth::id_usuario())
                    ->where('ano_folha', date('Y', strtotime($todos['data_abono'])))
                    ->where('mes_folha', date('m', strtotime($todos['data_abono'])))
                    ->where('id_lotacao', $todos['id_lotacao'])
                    ->first();

                if ($folha) {
                    return $response->withStatus(404)->withJson(['errorMessage' => 'A folha desse mês foi fechada!']);
                }

                $abono = Abono::where('situacao_abono', 'S')->where('data_abono', $todos['data_abono'])->where('periodo_abono', $todos['periodo_abono'])->where('id_usuario_criacao_abono', Auth::id_usuario())->first();
                if ($abono) {
                    return $response->withStatus(404)->withJson(['errorMessage' => 'Você ja possui um solicitação de abono com essa data e esse período.']);
                }

                if ($todos['periodo_abono'] != 'I') {
                    $abono = Abono::where('situacao_abono', 'S')->where('data_abono', $todos['data_abono'])->where('periodo_abono', 'I')->where('id_usuario_criacao_abono', Auth::id_usuario())->first();
                    if ($abono) {
                        return $response->withStatus(404)->withJson(['errorMessage' => 'Você ja possui um solicitação de abono com essa data do tipo Integral.']);
                    }
                }

                if ($todos['periodo_abono'] == 'I') {
                    $abono = Abono::where('situacao_abono', 'S')->where('data_abono', $todos['data_abono'])->where('id_usuario_criacao_abono', Auth::id_usuario())->first();
                    if ($abono) {
                        return $response->withStatus(404)->withJson(['errorMessage' => 'Você ja possui um solicitação de abono com essa data com outro periodo.']);
                    }
                }

                $abono = Abono::where('situacao_abono', 'S')->where('data_abono', '<=', $todos['data_abono'])->where('data_final_abono', '>=', $todos['data_abono'])->where('id_usuario_criacao_abono', Auth::id_usuario())->first();
                if ($abono) {
                    return $response->withStatus(404)->withJson(['errorMessage' => 'Você ja possui um solicitação de abono com essa data.']);
                }

                if (isset(($request->getParams())['data_final_abono'])) {
                    $todos['data_final_abono'] = ($request->getParams())['data_final_abono'];
                }

                $abono = Abono::create($todos);

                $AbonoHorario = AbonoHorario::create([
                    'id_abono' => $abono->id_abono,
                    'entrada_1_horario' => $entrada_1_horario,
                    'saida_1_horario' => $saida_1_horario,
                    'entrada_2_horario' => $entrada_2_horario,
                    'saida_2_horario' => $saida_2_horario
                ]);


                DB::commit();
                return $response->withStatus(200)->withJson([]);
            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        $lotacao = Lotacao::find(Auth::id_lotacao_exercicio_usuario())->toArray();

        $ano = $request->getQueryParam('ano') ?? null;
        $mes = $request->getQueryParam('mes') ?? null;
        $dia = $request->getQueryParam('dia') ?? null;

        return $this->view(
            $response,
            'abonos/servidor',
            'store',
            [
                'ano' => $ano,
                'mes' => $mes,
                'dia' => $dia,
                'lotacao' => $lotacao
            ]
        );
    }

    public function deferir(Request $request, Response $response, $args)
    {
        if ($request->getMethod() == 'POST') {
            DB::beginTransaction();
            try {

                $todos = [
                    'id_usuario_responsavel' => Auth::id_usuario(),
                    'mensagem_abono' => 'Solicitação de Abono Deferida',
                    'id_status_abono' => 5,
                    'id_usuario_atualizacao_abono' => Auth::id_usuario(),
                ];

                if ($args['id'] == '0') {
                    foreach (($request->getParams())['ids_abono'] as $id_abono) {
                        $abono = Abono::with('horario')->find($id_abono);
                        $usuario = Usuario::with('Orgao')->with('Lotacao')->find($abono->id_usuario_criacao_abono)->toArray();

                        if ($abono->horario) {
                            $horario = $abono->horario;
                        } else {
                            $horario = Horario::where(function ($query) use ($usuario) {
                                if ($usuario['id_horario']) {
                                    return $query->where('id_horario', $usuario['id_horario']);
                                }
                                return $query->leftJoin('horario_padrao', function ($query) use ($usuario) {
                                    $query->on('horario_padrao.id_horario', 'horario.id_horario')
                                        ->where('horario_padrao.status', 'S')
                                        ->where('horario_padrao.id_orgao', Auth::id_orgao_exercicio_usuario());
                                });
                            })->first();
                        }

                        if ($abono->data_final_abono) {
                            $data_inicial = Date::create($abono->data_abono);
                            $data_final = Date::create($abono->data_final_abono)->addDay(1);

                            $recurrency = new Recurrency();
                            $recurrency->startDate($data_inicial)
                                ->until($data_final)
                                ->freq('daily')
                                ->generateOccurrences();

                            foreach ($recurrency->occurrences as $NewData) {
                                // if (!$NewData->isWeekend()) {
                                $data_ponto = explode(' ', $NewData->toDateString());
                                $this->criando_ponto_abono($usuario, $abono, $horario, $data_ponto[0]);
                                // }
                            }
                        } else {
                            $this->criando_ponto_abono($usuario, $abono, $horario, $abono->data_abono);
                        }

                        $abono->update($todos);
                    }
                } else {
                    $abono = Abono::with('horario')->find($args['id']);
                    $usuario = Usuario::with('Orgao')->with('Lotacao')->find($abono->id_usuario_criacao_abono)->toArray();

                    if ($abono->horario) {
                        $horario = $abono->horario;
                    } else {

                        $horario = Horario::where(function ($query) use ($usuario) {
                            if ($usuario['id_horario']) {
                                return $query->where('id_horario', $usuario['id_horario']);
                            }
                            return $query->leftJoin('horario_padrao', function ($query) use ($usuario) {
                                $query->on('horario_padrao.id_horario', 'horario.id_horario')
                                    ->where('horario_padrao.status', 'S')
                                    ->where('horario_padrao.id_orgao', Auth::id_orgao_exercicio_usuario());
                            });
                        })->first();
                    }



                    if ($abono->data_final_abono) {
                        $data_inicial = Date::create($abono->data_abono);
                        $data_final = Date::create($abono->data_final_abono)->addDay(1);

                        $recurrency = new Recurrency();
                        $recurrency->startDate($data_inicial)
                            ->until($data_final)
                            ->freq('daily')
                            ->generateOccurrences();

                        foreach ($recurrency->occurrences as $NewData) {
                            // if (!$NewData->isWeekend()) {
                            $data_ponto = explode(' ', $NewData->toDateString());
                            $this->criando_ponto_abono($usuario, $abono, $horario, $data_ponto[0]);
                            // }
                        }
                    } else {
                        $this->criando_ponto_abono($usuario, $abono, $horario, $abono->data_abono);
                    }

                    $abono->update($todos);
                }

                DB::commit();
                return $response->withStatus(200)->withJson([]);
            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }
    }

    public function criando_ponto_abono($usuario, $abono, $horario, $data_abono)
    {

        $tamanho = 2;
        $tipo = [];
        $data = [];

        if ($abono->periodo_abono != 'I') {
            $horario->entrada_1_horario = $horario->entrada_1_horario ? $horario->entrada_1_horario : $horario->entrada_2_horario;
            $horario->saida_1_horario = $horario->saida_1_horario ? $horario->saida_1_horario : $horario->saida_2_horario;
            $horario->entrada_2_horario = $horario->entrada_2_horario ? $horario->entrada_2_horario : $horario->entrada_1_horario;
            $horario->saida_2_horario = $horario->saida_2_horario ? $horario->saida_2_horario : $horario->saida_1_horario;
        }

        if ($abono->periodo_abono == 'M') {
            $tipo[0] = '1';
            $tipo[1] = '2';
            $data[0] = date('Y-m-d H:i:s', strtotime($data_abono . ' ' . $horario->entrada_1_horario));
            $data[1] = date('Y-m-d H:i:s', strtotime($data_abono . ' ' . $horario->saida_1_horario));
        } else if ($abono->periodo_abono == 'V') {
            $tipo[0] = '3';
            $tipo[1] = '4';
            $data[0] = date('Y-m-d H:i:s', strtotime($data_abono . ' ' . $horario->entrada_2_horario));
            $data[1] = date('Y-m-d H:i:s', strtotime($data_abono . ' ' . $horario->saida_2_horario));
        } else if ($abono->periodo_abono == 'I') {
            $tamanho = 4;
            $tipo[0] = '1';
            $tipo[1] = '2';
            $tipo[2] = '3';
            $tipo[3] = '4';
            if ($horario->entrada_1_horario) {
                $data[0] = date('Y-m-d H:i:s', strtotime($data_abono . ' ' . $horario->entrada_1_horario));
            }
            if ($horario->saida_1_horario) {
                $data[1] = date('Y-m-d H:i:s', strtotime($data_abono . ' ' . $horario->saida_1_horario));
            }
            if ($horario->entrada_2_horario) {
                $data[2] = date('Y-m-d H:i:s', strtotime($data_abono . ' ' . $horario->entrada_2_horario));
            }
            if ($horario->saida_2_horario) {
                $data[3] = date('Y-m-d H:i:s', strtotime($data_abono . ' ' . $horario->saida_2_horario));
            }
        }

        $mongoDB = new MongoDB();

        for ($i = 0; $i < $tamanho; $i++) {
            $mongoDB->insert([
                'id_usuario' => (int) $usuario['id_usuario'],
                'tipo_ponto' => (int) $tipo[$i],
                'data_ponto' => $data[$i],
                'ip_ponto' => Env::ip(),
                'dados_ponto' => $_SERVER['HTTP_USER_AGENT'],
                'matricula_usuario' => (int) $usuario['matricula_usuario'],
                'contrato_usuario' => (int) $usuario['contrato_usuario'],
                'cargo_usuario' => $usuario['cargo_usuario'],
                'cargo_comissao_usuario' => $usuario['cargo_comissao_usuario'],
                'email_usuario' => $usuario['email_usuario'],
                'id_lotacao' => (int) $abono['id_lotacao'],
                'id_orgao' => (int) $usuario['id_orgao_exercicio_usuario'],
                'descricao_orgao' => $usuario['orgao']['descricao_orgao'],
                'sigla_orgao' => $usuario['orgao']['sigla_orgao'],
                'descricao_lotacao' => $usuario['lotacao']['descricao_lotacao'],
                'nome_usuario' => $usuario['nome_usuario'],
                'finalidade_ponto' => 'ABONO',
                'data_criacao_ponto' => date('Y-m-d H:i:s'),
                'data_atualizacao_ponto' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($abono->periodo_abono == 'M') {
            $mongoDB = new MongoDB();
            $mongoDB->setFilter('finalidade_ponto', 'PONTO');
            $mongoDB->setFilter('data_ponto', new \MongoDB\BSON\Regex($data_abono));
            $mongoDB->setFilter('id_usuario', (int) $usuario['id_usuario']);
            $mongoDB->setFilter('$or', [['tipo_ponto' => 1], ['tipo_ponto' => 2]]);

            $pontos_para_abonar = $mongoDB->executeQuery();

            foreach ($pontos_para_abonar as $ponto_para_abonar) {
                $mongoDB->update($ponto_para_abonar->_id, [
                    '$set' => [
                        'finalidade_ponto' => 'HISTORICO',
                        'data_atualizacao_ponto' => date('Y-m-d H:i:s')
                    ]
                ]);
            }
        } else if ($abono->periodo_abono == 'V') {
            $mongoDB = new MongoDB();
            $mongoDB->setFilter('finalidade_ponto', 'PONTO');
            $mongoDB->setFilter('data_ponto', new \MongoDB\BSON\Regex($data_abono));
            $mongoDB->setFilter('id_usuario', (int) $usuario['id_usuario']);
            $mongoDB->setFilter('or', [['tipo_ponto' => 3], ['tipo_ponto' => 4]]);
            $pontos_para_abonar = $mongoDB->executeQuery();

            foreach ($pontos_para_abonar as $ponto_para_abonar) {
                $mongoDB->update($ponto_para_abonar->_id, [
                    '$set' => [
                        'finalidade_ponto' => 'HISTORICO',
                        'data_atualizacao_ponto' => date('Y-m-d H:i:s')
                    ]
                ]);
            }
        } else if ($abono->periodo_abono == 'I') {
            $mongoDB = new MongoDB();
            $mongoDB->setFilter('finalidade_ponto', 'PONTO');
            $mongoDB->setFilter('data_ponto', new \MongoDB\BSON\Regex($data_abono));
            $mongoDB->setFilter('id_usuario', (int) $usuario['id_usuario']);
            $pontos_para_abonar = $mongoDB->executeQuery();

            foreach ($pontos_para_abonar as $ponto_para_abonar) {
                $mongoDB->update($ponto_para_abonar->_id, [
                    '$set' => [
                        'finalidade_ponto' => 'HISTORICO',
                        'data_atualizacao_ponto' => date('Y-m-d H:i:s')
                    ]
                ]);
            }
        }
    }

    public function indeferir(Request $request, Response $response, $args)
    {
        if ($request->getMethod() == 'POST') {
            DB::beginTransaction();
            try {

                $todos = [
                    'id_usuario_responsavel' => Auth::id_usuario(),
                    'mensagem_indeferido_abono' => ($request->getParams())['mensagem_indeferido_abono'],
                    'id_status_abono' => 4,
                    'id_usuario_atualizacao_abono' => Auth::id_usuario(),
                ];

                if ($args['id'] == '0') {
                    $ids_abono = explode('-', ($request->getParams())['ids_abono']);
                    foreach ($ids_abono as $id_abono) {
                        if ($id_abono !== '') {
                            $abono = Abono::find($id_abono);

                            $abono->update($todos);
                        }
                    }
                } else {
                    $abono = Abono::find($args['id']);

                    $abono->update($todos);
                }

                DB::commit();
                return $response->withStatus(200)->withJson([]);
            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }
    }

    public function devolver(Request $request, Response $response, $args)
    {
        if ($request->getMethod() == 'POST') {
            DB::beginTransaction();
            try {

                $todos = [
                    'id_usuario_responsavel' => Auth::id_usuario(),
                    'mensagem_abono' => ($request->getParams())['mensagem_abono'],
                    'id_status_abono' => 3,
                    'id_usuario_atualizacao_abono' => Auth::id_usuario(),
                ];

                $abono = Abono::find($args['id']);

                $abono->update($todos);

                DB::commit();
                return $response->withStatus(200)->withJson([]);
            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }
    }

    public function update(Request $request, Response $response, $args)
    {
        if ($request->getMethod() == 'POST') {
            DB::beginTransaction();
            try {

                $todos = [
                    'motivo_resposta_abono' => ($request->getParams())['motivo_resposta_abono'],
                    'id_status_abono' => 2,
                    'id_usuario_atualizacao_abono' => Auth::id_usuario(),
                ];

                $abono = Abono::find($args['id']);

                $abono->update($todos);

                DB::commit();
                return $response->withStatus(200)->withJson([]);
            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        $abono = Abono::with('StatusAbono')->find($args['id'])->toArray();

        return $this->view(
            $response,
            'abonos/servidor',
            'update',
            ['abono' => $abono]
        );
    }

    public function destroy(Request $request, Response $response, $args)
    {
        if ($request->getMethod() == 'DELETE') {
            DB::beginTransaction();
            try {

                $abono = Abono::find($args['id']);

                $abono->update([
                    'situacao_abono' => 'N',
                    'id_usuario_atualizacao_abono' => Auth::id_usuario(),
                ]);

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
        $id_usuario = isset(($request->getParams())['id_usuario']) ? ($request->getParams())['id_usuario'] : false;
        $ano = isset(($request->getParams())['ano']) ? ($request->getParams())['ano'] : false;
        $mes = isset(($request->getParams())['mes']) ? ($request->getParams())['mes'] : false;
        $id_orgao = ($request->getParams())['id_orgao'] ?? false;
        $abonos_finalizados = isset(($request->getParams())['abonos_finalizados']) ? ($request->getParams())['abonos_finalizados'] : false;

        $order = isset(($request->getParams())['order']) ? ($request->getParams())['order'][0] : null;

        if ((Auth::perfil_usuario())['id_tipo_perfil'] <> 1) {
            $Lotacoes = MinhasLotacoes::lotacoes();
            $MinhasLotacoes = [];

            $ultimoDiaMes = str_pad($ano, 4, '20', STR_PAD_LEFT) . "-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-31 23:59:59";
            $primeiroDiaMes = str_pad($ano, 4, '20', STR_PAD_LEFT) . "-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";

            foreach ($Lotacoes as $dados) {
                if ($dados['status_lotacao_responsavel'] == 'A' or ($dados['data_criacao_lotacao_responsavel'] <= $ultimoDiaMes and $dados['data_atualizacao_lotacao_responsavel'] >= $primeiroDiaMes)) {
                    $MinhasLotacoes[] = $dados['id_lotacao'];
                }
            }
        }

        $group_order = [
            0 => 'usuario.matricula_usuario',
            1 => 'usuario.contrato_usuario',
            2 => 'usuario.cpf_usuario',
            3 => 'usuario.nome_usuario',
            4 => 'abono.data_abono',
            5 => 'abono.periodo_abono'
        ];

        if (!$order) {
            $order['column'] = 4;
            $order['dir'] = 'asc';
        }

        if ($id_usuario == 'id_usuario') {
            $group_order = [
                0 => 'abono.data_abono',
                1 => 'abono.motivo_abono',
                2 => 'abono.periodo_abono',
            ];

            if (!$order) {
                $order['column'] = 0;
            }
        }

        $abonos = Abono::select([
            'abono.*',
            'usuario.*',
            'responsavel.nome_usuario AS nome_reponsavel',
            'status_abono.*'
        ])->join('status_abono', 'abono.id_status_abono', 'status_abono.id_status_abono')
            ->join('usuario', 'abono.id_usuario_criacao_abono', 'usuario.id_usuario')
            ->join('orgao', 'usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
            ->join('lotacao', 'abono.id_lotacao', 'lotacao.id_lotacao')
            ->leftJoin('usuario AS responsavel', 'abono.id_usuario_responsavel', 'responsavel.id_usuario')
            ->where(function ($query) use ($id_usuario, $MinhasLotacoes) {
                if ($id_usuario != 'id_usuario') {
                    if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2) {
                        $query->whereIn('orgao.id_orgao',  function ($query) {
                            $query->select('id_orgao')
                                ->from(with(new OrgaoResponsavel())->getTable())
                                ->where('id_usuario', Auth::id_usuario());
                        });
                    } else if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
                        $query->whereIn('lotacao.id_lotacao',  $MinhasLotacoes);
                    }
                }
            })
            ->where(function ($query) use ($id_usuario) {
                if ($id_usuario == 'id_usuario') {
                    $query->where('usuario.id_usuario', Auth::id_usuario());
                }
            })
            ->where(function ($query) use ($id_usuario) {
                if ($id_usuario != 'id_usuario') {
                    $query->where('abono.id_status_abono', '!=', 3)
                        ->where('abono.id_usuario_criacao_abono', '!=', Auth::id_usuario());
                }
            })
            ->where(function ($query) use ($ano, $mes) {
                if ($ano && $mes) {
                    $data = Date::create($ano, $mes);
                    $dia_mes_ano_inicial = ($data->startOfMonth())->toDateString();
                    $dia_mes_ano_final = ($data->endOfMonth())->toDateString();

                    $query->where('abono.data_abono', '>=', $dia_mes_ano_inicial)
                        ->where(function ($query) use ($dia_mes_ano_final) {
                            $query->where('abono.data_abono', '<=', $dia_mes_ano_final)
                                ->orWhere('abono.data_final_abono', '<=', $dia_mes_ano_final);
                        });
                }
            })
            ->where(function ($query) use ($abonos_finalizados) {
                if ($abonos_finalizados) {
                    if ($abonos_finalizados === 'false') {
                        $query->where('abono.id_status_abono', '!=', 4)->where('abono.id_status_abono', '!=', 5);
                    }
                }
            })
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->where('abono.data_abono', 'LIKE', $search)
                        ->orWhere('abono.motivo_abono', 'LIKE', $search)
                        ->orWhere('abono.mensagem_abono', 'LIKE', $search)
                        ->orWhere('abono.periodo_abono', 'LIKE', $search)
                        ->orWhere('usuario.nome_usuario', 'LIKE', $search)
                        ->orWhere('responsavel.nome_usuario', 'LIKE', $search);
                }
            })
            ->where(function ($query) use ($id_orgao) {
                if ($id_orgao) {
                    $query->where('orgao.id_orgao', $id_orgao);
                }
            })
            ->where('abono.situacao_abono', 'S')
            ->orderBy('status_abono.id_status_abono')
            ->orderBy($group_order[$order['column']], $order['dir'])
            ->paginate($length, ['*'], 'page', $current_page)->toArray();

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $abonos['to'],
            'iTotalDisplayRecords' => $abonos['total'],
            'aaData' => $abonos['data'],
            'lotacoes' => $abonos_finalizados,
        ];

        return $response->withStatus(200)->withJson($resposta);
    }

    public function pontos_devolvidos(Request $request, Response $response, $args)
    {
        $total_devolvidos = Abono::where('id_usuario_criacao_abono', Auth::id_usuario())
            ->where('id_status_abono', 3)
            ->where('abono.situacao_abono', 'S')
            ->count();


        return $response->withStatus(200)->withJson($total_devolvidos);
    }

    public function pontos_retornados(Request $request, Response $response, $args)
    {
        if (date('m') == '01') {
            $ultimoDiaMes = intval(date('Y') - 1) . "-12-31 23:59:59";
            $primeiroDiaMes = intval(date('Y') - 1) . "-12-01 00:00:00";
        } else {
            $ultimoDiaMes = date('Y') . "-" . str_pad(intval(date('m') - 1), 2, '0', STR_PAD_LEFT) . "-31 23:59:59";
            $primeiroDiaMes = date('Y') . "-" . str_pad(intval(date('m') - 1), 2, '0', STR_PAD_LEFT) . "-01 00:00:00";
        }

        if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
            $Lotacoes = MinhasLotacoes::lotacoes();
            $MinhasLotacoes = [];


            foreach ($Lotacoes as $dados) {
                if ($dados['status_lotacao_responsavel'] == 'A' or ($dados['data_criacao_lotacao_responsavel'] <= $ultimoDiaMes and $dados['data_atualizacao_lotacao_responsavel'] >= $primeiroDiaMes)) {
                    $MinhasLotacoes[] = $dados['id_lotacao'];
                }
            }
        }


        $total_retornados = Abono::join('status_abono', 'abono.id_status_abono', 'status_abono.id_status_abono')
            ->join('usuario', 'usuario.id_usuario', 'abono.id_usuario_criacao_abono')
            ->join('orgao', 'usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
            ->join('lotacao', 'abono.id_lotacao', 'lotacao.id_lotacao')
            ->where(function ($query) use ($MinhasLotacoes) {
                if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2 || (Auth::perfil_usuario())['id_tipo_perfil'] == 4) {
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                } else if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
                    $query->whereIn('lotacao.id_lotacao',  $MinhasLotacoes);
                }
            })
            ->where(function ($query) {
                $query->where('abono.id_status_abono', 2)->orWhere('abono.id_status_abono', 1);
            })
            ->where(function ($query) use ($primeiroDiaMes, $ultimoDiaMes) {
                if (intval(Env::get('dia_fechamento_folha')) >= intval(date('d'))) {
                    $query->where('abono.data_abono', '>=', $primeiroDiaMes)
                        ->where('abono.data_abono', '<=', $ultimoDiaMes);
                } else {
                    $query->where('abono.data_abono', '>=', date('Y-m-d H:i:s', strtotime('+1 month', strtotime($primeiroDiaMes))))
                        ->where('abono.data_abono', '<=', date('Y-m-d H:i:s', strtotime('+1 month', strtotime($ultimoDiaMes))));
                }
            })
            //->where('abono.data_abono', '>=', $primeiroDiaMes)
            //->where('abono.data_abono', '<=', $ultimoDiaMes)
            ->where('abono.situacao_abono', 'S')
            ->where('id_usuario_criacao_abono', '!=', Auth::id_usuario())
            ->count();

        return $response->withStatus(200)->withJson($total_retornados);
    }

    public function servidor(Request $request, Response $response, $args)
    {
        $usuario = Usuario::with('TipoUsuario')->with('Orgao')->with('Lotacao')->find($args['id'])->toArray();

        $ano = isset(($request->getParams())['ano']) ? ($request->getParams())['ano'] : false;
        $mes = isset(($request->getParams())['mes']) ? ($request->getParams())['mes'] : false;

        $abonos = Abono::with('StatusAbono')
            ->where('abono.id_usuario_criacao_abono', $args['id'])
            ->where(function ($query) {
                $query->where('abono.id_status_abono', 1)
                    ->orWhere('abono.id_status_abono', 2);
            })
            ->where(function ($query) use ($ano, $mes) {
                if ($ano && $mes) {
                    $data = Date::create($ano, $mes);
                    $dia_mes_ano_inicial = ($data->startOfMonth())->toDateString();
                    $dia_mes_ano_final = ($data->endOfMonth())->toDateString();

                    $query->where('abono.data_abono', '>=', $dia_mes_ano_inicial)
                        ->where(function ($query) use ($dia_mes_ano_final) {
                            $query->where('abono.data_abono', '<=', $dia_mes_ano_final)
                                ->orWhere('abono.data_final_abono', '<=', $dia_mes_ano_final);
                        });
                }
            })
            ->where('abono.situacao_abono', 'S')
            ->orderBy('abono.data_abono')
            ->get()->toArray();

        return $this->view(
            $response,
            'abonos',
            'servidor',
            [
                'usuario' => $usuario,
                'abonos' => $abonos,
                'ano' => $ano,
                'mes' => $mes,
            ]
        );
    }
}
