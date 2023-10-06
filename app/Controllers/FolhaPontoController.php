<?php

namespace App\Controllers;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Extensions\Support\Recurrency;
use App\Extensions\Support\Hash;
use App\Extensions\Support\Date;
use App\Classes\MinhasLotacoes;

use App\Models\{
    Usuario,
    Ponto,
    Calendario,
    Ferias,
    Horario,
    Hierarquia,
    Lotacao,
    OrgaoResponsavel,
    LotacaoResponsavel,
    PerfilUsuario,
    Dispensa,
    Escala,
    DataEscala,
    Folha,
    Abono,
    Afastamento,
    Faltas
};

use App\Utils\Auth;

use App\Classes\{
    MongoDB,
    LDAP
};

use DateTime;

class FolhaPontoController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        $anos = [];
        for ($i = 2019; $i <= date('Y'); $i++) {
            $anos[] = $i;
        }

        return $this->view(
            $response,
            'folhas_ponto',
            'index',
            [
                'years' => $anos,
                'ano' => $request->getQueryParam('ano') !== '' ? $request->getQueryParam('ano') : date('Y'),
                'mes' => $request->getQueryParam('mes') !== '' ? $request->getQueryParam('mes') : date('m'),
            ]
        );
    }

    public function show(Request $request, Response $response, $args)
    {

        if ($request->getMethod() == 'POST') {
            DB::beginTransaction();
            try {

                $ldap = new LDAP();
                $ldap->setLogin(explode('@', Auth::email_usuario())[0]);
                $ldap->setPassword(($request->getParams())['password']);

                $user = [];
                if (!$user = $ldap->verify_login()) {
                    return $response->withStatus(405)->withJson(['errorMessage' => 'Senha incorreta!']);
                }

                $id_usuario = $request->getQueryParam('id_usuario');
                $ano = $request->getQueryParam('ano');
                $mes = $request->getQueryParam('mes');
                $id_lotacao_servidor = $request->getParams()['id_lotacao_servidor'] ?? null;

                $abono = Abono::where('id_usuario_criacao_abono', $id_usuario)
                    ->whereRaw("YEAR(data_abono) = $ano")
                    ->whereRaw("MONTH(data_abono) = $mes")
                    ->where(function ($query) use ($id_lotacao_servidor) {
                        if ($id_lotacao_servidor) {
                            return $query->where('id_lotacao', $id_lotacao_servidor);
                        }

                        return $query->whereNull('id_lotacao');
                    })
                    ->where('situacao_abono', '!=', 'N')
                    ->where('id_status_abono', '!=', 4)
                    ->where('id_status_abono', '!=', 5)
                    ->first();

                if ($abono) {
                    return $response->withStatus(406)->withJson(['errorMessage' => 'Este servidor possui pendências de abono.']);
                }

                $folha = Folha::where('id_usuario', $id_usuario)
                    ->where('ano_folha', $request->getQueryParam('ano'))
                    ->where('mes_folha', $request->getQueryParam('mes'))
                    ->where(function ($query) use ($id_lotacao_servidor) {
                        if ($id_lotacao_servidor) {
                            return $query->where('id_lotacao', $id_lotacao_servidor);
                        }

                        return $query->whereNull('id_lotacao');
                    })
                    ->first();

                if ($folha) {
                    return $response->withStatus(407)->withJson(['errorMessage' => 'Esta folha já foi assinada!']);
                }

                $responsavel = Usuario::with('Lotacao')->find(Auth::id_usuario())->toArray();

                $todos = [
                    'id_usuario' => $id_usuario,
                    'id_lotacao' => $id_lotacao_servidor,
                    'nome_usuario_responsavel' => $responsavel['nome_usuario'],
                    'cargo_usuario_responsavel' => $responsavel['cargo_usuario'] ? $responsavel['cargo_usuario'] : null,
                    'cargo_comissao_usuario_responsavel' => $responsavel['cargo_comissao_usuario'] ? $responsavel['cargo_comissao_usuario'] : null,
                    'descricao_lotacao_usuario_responsavel' => $responsavel['lotacao']['descricao_lotacao'],
                    'ano_folha' => $ano,
                    'mes_folha' => $mes,
                    'total_assinaturas' => ($request->getParams())['total_assinaturas'],
                    'token_folha' => Hash::randomize(16)
                ];

                Folha::create($todos);

                DB::commit();
                return $response->withStatus(200)->withJson([]);
            } catch (\Throwable $th) {
                DB::rollBack();
                return $response->withStatus(404)->withJson(['errorMessage' => $th->getMessage()]);
            }
        }

        $data = Date::create($request->getQueryParam('ano'), $request->getQueryParam('mes'));

        $id_usuario = $request->getQueryParam('id_usuario');
        $usuario = Usuario::with('Orgao')->with('Lotacao')->with('TipoUsuario')->find($id_usuario)->toArray();

        $responsaveis = MinhasLotacoes::lotacoes();
        $ultimoDiaMes = str_pad($request->getQueryParam('ano'), 4, '20', STR_PAD_LEFT) . "-" . str_pad($request->getQueryParam('mes'), 2, '0', STR_PAD_LEFT) . "-31 23:59:59";
        $primeiroDiaMes = str_pad($request->getQueryParam('ano'), 4, '20', STR_PAD_LEFT) . "-" . str_pad($request->getQueryParam('mes'), 2, '0', STR_PAD_LEFT) . "-01 00:00:00";

        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;

        if ($id_lotacao) {
            $lotacao_atual = Lotacao::find($id_lotacao)->toArray();
        }

        $dia_mes_ano_inicial = ($data->startOfMonth())->toDateString();
        $dia_mes_ano_final = ($data->endOfMonth())->toDateString();

        $mongoDB = new MongoDB();
        $query = [
            '$or' => [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']],
            'data_ponto' => ['$gte' => $dia_mes_ano_inicial, '$lte' => $dia_mes_ano_final],
            'id_usuario' => (int) $id_usuario
        ];
        $lotacoes_ = $mongoDB->distinct('id_lotacao', $query);

        $lotacoes = [];

        if (Auth::perfil_usuario() && (Auth::perfil_usuario())['id_tipo_perfil'] != 3) {
            foreach ($lotacoes_[0]->values as $id_lotacao_) {
                try {
                    $lotacao = Lotacao::find($id_lotacao_);
                    $lotacoes[] = $lotacao->toArray();
                } catch (\Throwable $th) {
                }
            }
        }
        if (Auth::perfil_usuario() && (Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
            foreach ($lotacoes_[0]->values as $id_lotacao_) {
                foreach ($responsaveis as $responsavel) {
                    if ((($responsavel['status_lotacao_responsavel'] == 'R' and  $responsavel['data_atualizacao_lotacao_responsavel'] >= $primeiroDiaMes and $responsavel['data_criacao_lotacao_responsavel'] <= $ultimoDiaMes) or $responsavel['status_lotacao_responsavel'] == 'A') and $responsavel['id_lotacao'] == $id_lotacao_) {
                        $lotacao = Lotacao::find($id_lotacao_);
                        $lotacoes[] = $lotacao->toArray();
                        break;
                    }
                }
            }
        }

        if (count($lotacoes) == 0) {
            $lotacoes[] = $lotacao_atual ?? $usuario['lotacao'];
        }

        $folha = Folha::where('id_usuario', $id_usuario)
            ->where('ano_folha', $request->getQueryParam('ano'))
            ->where('mes_folha', $request->getQueryParam('mes'))
            ->whereNotNull('id_lotacao')
            ->get();

        $minhaAssinatura = Folha::where('folha.id_usuario', $id_usuario)
            ->where('ano_folha', $request->getQueryParam('ano'))
            ->where('mes_folha', $request->getQueryParam('mes'))
            ->whereNull('id_lotacao')
            ->first();

        if (!$lotacao_atual and count($lotacoes) == 1) {
            $lotacao_atual = $lotacoes[0];
            if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
                $id_lotacao = $lotacoes[0]['id_lotacao'];
            }
        }

        $parcial = 'N';
        //if( intval($folha[0]['total_assinaturas']) > count($lotacoes_[0]->values) ){
        if (intval($folha[0]['total_assinaturas']) > count($folha)) {
            $parcial = 'S';
        }
        if ($parcial == 'N' and count($folha) > 0) {
            $assinado = 'S';
        } else {
            $assinado = 'N';
            foreach ($folha as $dados) {
                if ($dados['id_lotacao'] == $lotacao_atual['id_lotacao']) {
                    $assinado = 'S';
                }
            }
        }

        $lotacao_usuario = Lotacao::find(Auth::id_lotacao_exercicio_usuario())->toArray();


        /* VERIFICAR SE PODE ASSINAR A FOLHA */
        $autorizadoAssinar = true;
        if (Auth::perfil_usuario()['id_tipo_perfil'] == 2) {
            $gestor = OrgaoResponsavel::where('id_usuario', Auth::id_usuario())->where('id_orgao', $usuario['orgao']['id_orgao'])->where('funcao', '!=', '3')->first();

            if (!$gestor) {
                $autorizadoAssinar = false;
                try {
                    $minhasLotacoes = MinhasLotacoes::lotacoesArray();
                } catch (\Throwable $th) {
                    $minhasLotacoes = [];
                }

                foreach ($lotacoes as $lotacao) {
                    if (in_array($lotacao['id_lotacao'], $minhasLotacoes)) {
                        $autorizadoAssinar = true;
                    }
                }
            }
        }

        $dados = $this->registro($request, $response, $args, $id_lotacao);
        $dados['lotacoes'] = $lotacoes;
        $dados['id_lotacao'] = $id_lotacao;
        $dados['lotacao_atual'] = $lotacao_atual;
        $dados['usuario'] = $usuario;
        $dados['folha'] = $folha ? $folha->toArray() : null;
        $dados['lotacao_usuario'] = $lotacao_usuario;
        $dados['assinado'] = $assinado;
        $dados['minhaAssinatura'] = $minhaAssinatura;
        $dados['minhaAssinaturaStatus'] = $minhaAssinatura ? 'S' : 'N';
        $dados['parcial'] = $parcial;
        $dados['total_assinaturas'] = count($lotacoes_[0]->values);
        $dados['autorizadoAssinar'] = $autorizadoAssinar;

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

        $dados['years'] = [];
        for ($i = 2019; $i <= date('Y'); $i++) {
            $dados['years'][] = $i;
        }

        return $this->view(
            $response,
            'folhas_ponto',
            'show',
            $dados
        );
    }

    public function print(Request $request, Response $response, $args)
    {
        $id_usuario = $request->getQueryParam('id_usuario');
        $usuario = Usuario::with('Orgao')->with('Lotacao')->find($id_usuario)->toArray();

        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;


        $folha = Folha::where('id_usuario', $id_usuario)
            ->where('ano_folha', $request->getQueryParam('ano'))
            ->where('mes_folha', $request->getQueryParam('mes'))
            ->whereNotNull('id_lotacao')
            ->get();

        $minhaAssinatura = Folha::where('folha.id_usuario', $id_usuario)
            ->where('ano_folha', $request->getQueryParam('ano'))
            ->where('mes_folha', $request->getQueryParam('mes'))
            ->whereNull('id_lotacao')
            ->first();

        $dados = $this->registro($request, $response, $args, $id_lotacao);
        $dados['folha'] = $folha ? $folha->toArray() : null;
        $dados['minhaAssinatura'] = $minhaAssinatura;
        $dados['lotacao_atual'] = $id_lotacao;

        //VERIFICA A PRIMEIRA BATIDA
        $horario = Horario::where(function ($query) use ($usuario, $dados) {
            if ($dados['ids_horario'][0]) {
                return $query->where('id_horario', $dados['ids_horario'][0]);
            } else if ($usuario['id_horario']) {
                return $query->where('id_horario', $usuario['id_horario']);
            }
            return $query->leftJoin('horario_padrao', function ($query) use ($usuario) {
                $query->on('horario_padrao.id_horario', 'horario.id_horario')
                    ->where('horario_padrao.status', 'S')
                    ->where('horario_padrao.id_orgao', $usuario['id_orgao_exercicio_usuario']);
            });
        })->first();

        $dados['usuario'] = $usuario;

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

        return $this->view(
            $response,
            'print',
            'print',
            $dados
        );
    }

    public function registro(Request $request, Response $response, $args,  $id_lotacao = null)
    {

        if ($request->getQueryParam('new') != 'false') {
            $pontos = [];
            $lotacao = [];
            $anos = [];
            $ids_horario = [];

            $mesSelecionado = str_pad($request->getQueryParam('mes'), 2, '0', STR_PAD_LEFT);
            $anoSelecionado = str_pad($request->getQueryParam('ano'), 4, '20', STR_PAD_LEFT);
            $diasSemana = [
                'Domingo',
                'Segunda-Feira',
                'Terça-Feira',
                'Quarta-Feira',
                'Quinta-Feira',
                'Sexta-Feira',
                'Sábado'
            ];

            $validacao = $request->getQueryParam('validacao') ? true : false;
            $id_usuario = $request->getQueryParam('id_usuario');


            $primeiroDiaMes = date($anoSelecionado . '-' . $mesSelecionado . '-01 00:00:00');
            $ultimoDiaMes =  date('Y-m-t H:i:s', strtotime($anoSelecionado . '-' . $mesSelecionado . '-01 23:59:59'));

            $usuario = Usuario::with('Orgao')->with('Lotacao')->find($id_usuario)->toArray();
            $lotacoes_responsavel = MinhasLotacoes::lotacoes();

            //VERIFICA NO PONTO DA SEICT
            if (($usuario['data_criacao_usuario'] >= '2022-03-01' and $usuario['data_admissao_usuario'] <= '2022-03-01') and  $primeiroDiaMes <= '2022-03-01') {
                $pontos = $this->pontos_anterior_2021($usuario, $request->getQueryParam('ano'), $request->getQueryParam('mes'));
            } else {

                for ($i = 1; $i <= date('t', strtotime($ultimoDiaMes)); $i++) {
                    $diaSelecionado = str_pad($i, 2, '00', STR_PAD_LEFT);
                    $diaSemana = date('w', strtotime($anoSelecionado . '-' . $mesSelecionado . '-' . $diaSelecionado));
                    $pontos[$diaSelecionado] = [
                        'pontos' => [],
                        'pontos_horarios' => [],
                        'abonos' => [],
                        'solicitacoes_abono' => [],
                        'abono_indeferido' => [],
                        'ferias' => '',
                        'afastamentos' => '',
                        'dispensas' => [],
                        'escalas' => [],
                        'datas' => [],
                        'dia_nome' => $diasSemana[$diaSemana],
                        'fim_de_semana' => in_array($diaSemana, [0, 6]) ? true : false,
                        'horas_trabalhadas' => [],
                    ];
                }

                //PROCURA OS PONTOS BATIDO
                $mongoDB = new MongoDB();
                $mongoDB->setFilter('$or', [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']]);
                $mongoDB->setFilter('data_ponto', ['$gte' => $primeiroDiaMes, '$lte' => $ultimoDiaMes]);
                $mongoDB->setFilter('id_usuario', (int) $usuario['id_usuario']);
                $mongoDB->setOptions('sort', ['tipo_ponto' => 1]);

                if (Auth::perfil_usuario() && (Auth::perfil_usuario())['id_tipo_perfil'] == 3 and $validacao != true) {
                    if ($lotacoes_responsavel) {
                        if ($id_lotacao) {
                            foreach ($lotacoes_responsavel as $responsavel) {
                                if ((($responsavel['status_lotacao_responsavel'] == 'R' and  $responsavel['data_atualizacao_lotacao_responsavel'] >= $primeiroDiaMes and $responsavel['data_criacao_lotacao_responsavel'] <= $ultimoDiaMes) or $responsavel['status_lotacao_responsavel'] == 'A') and $responsavel['id_lotacao'] == $id_lotacao) {
                                    $mongoDB->setFilter('id_lotacao', (int) $responsavel['id_lotacao']);
                                    break;
                                }
                            }
                        }
                    } else {
                        $mongoDB->setFilter('id_lotacao', 0);
                    }
                } else if ((Auth::perfil_usuario() && (Auth::perfil_usuario())['id_tipo_perfil'] != 3) or $validacao == true or Auth::id_usuario() == $id_usuario) {
                    if ($id_lotacao) {
                        $mongoDB->setFilter('id_lotacao', (int) $id_lotacao);
                    }
                }

                $registros_ponto = $mongoDB->executeQuery();

                foreach ($registros_ponto as $registro) {

                    if ($registro->id_horario) {
                        $ids_horario[] = $registro->id_horario;
                        $ids_horario = array_unique($ids_horario);
                    }

                    $pontos[date('d', strtotime($registro->data_ponto))][($registro->finalidade_ponto == 'ABONO' ?  'abonos' : 'pontos')][$registro->tipo_ponto] = date('H:i', strtotime($registro->data_ponto));

                    if ($registro->finalidade_ponto == 'PONTO' && $registro->id_horario) {
                        $pontos[date('d', strtotime($registro->data_ponto))]['pontos_horarios'][$registro->tipo_ponto] = array_keys($ids_horario, $registro->id_horario)[0] + 1;
                    }
                }

                //ABONOS PENDENTES/INDEFERIDOS
                $abonoSolicitados = Abono::where('data_abono', '<=', $ultimoDiaMes)
                    ->where('data_abono', '>=', $primeiroDiaMes)
                    ->where('situacao_abono', '!=', 'N')
                    ->whereNotIn('id_status_abono', [5])
                    ->where(function ($query) use ($primeiroDiaMes, $ultimoDiaMes) {
                        $query->whereNull('data_final_abono')
                            ->orWhere(function ($query) use ($primeiroDiaMes, $ultimoDiaMes) {
                                $query->where('data_final_abono', '>=', $primeiroDiaMes)
                                    ->where('data_final_abono', '<=', $ultimoDiaMes);
                            });
                    })
                    ->where(function ($query) use ($id_lotacao) {
                        if ($id_lotacao) {
                            $query->where('id_lotacao', $id_lotacao);
                        }
                    })
                    ->where('id_usuario_criacao_abono', $usuario['id_usuario'])
                    ->get();

                foreach ($abonoSolicitados as $abono) {
                    $abono->data_final_abono = $abono->data_final_abono ?? $abono->data_abono;
                    $diaInicial = ($abono->data_abono <= date($anoSelecionado . '-' . $mesSelecionado . '-01')) ? '01' : date('d', strtotime($abono->data_abono));
                    $diaFinal = ($abono->data_final_abono >= date($anoSelecionado . '-' . $mesSelecionado . '-t')) ? date('t', strtotime($anoSelecionado . '-' . $mesSelecionado . '-01')) : date('d', strtotime($abono->data_final_abono));

                    for ($i = intval($diaInicial); $i <= intval($diaFinal); $i++) {
                        if ($abono->id_status_abono == 4) {
                            $pontos[str_pad($i, 2, '00', STR_PAD_LEFT)]['abono_indeferido'] = $abono;
                        } else {
                            $pontos[str_pad($i, 2, '00', STR_PAD_LEFT)]['solicitacoes_abono'] = $abono;
                        }
                    }
                }

                /* CALCULA AS HORAS TRABALHADAS E ABONADAS */
                foreach ($pontos as $i => $ponto) {
                    $primeiroTurnoAbono = ($ponto['abonos'][1] && $ponto['abonos'][2]) ? (new DateTime($anoSelecionado . "-" . $mesSelecionado . "-" . $i . " " . $ponto['abonos'][2] . ":00"))->diff(new DateTime($anoSelecionado . "-" . $mesSelecionado . "-" . $i . " " . $ponto['abonos'][1] . ":00")) : 0;
                    $segundoTurnoAbono = ($ponto['abonos'][3] && $ponto['abonos'][4]) ? (new DateTime($anoSelecionado . "-" . $mesSelecionado . "-" . $i . " " . $ponto['abonos'][4] . ":00"))->diff(new DateTime($anoSelecionado . "-" . $mesSelecionado . "-" . $i . " " . $ponto['abonos'][3] . ":00")) : 0;

                    $primeiroTurno = ($ponto['pontos'][1] && $ponto['pontos'][2]) ? (new DateTime($anoSelecionado . "-" . $mesSelecionado . "-" . $i . " " . $ponto['pontos'][2] . ":00"))->diff(new DateTime($anoSelecionado . "-" . $mesSelecionado . "-" . $i . " " . $ponto['pontos'][1] . ":00")) : 0;
                    $segundoTurno = ($ponto['pontos'][3] && $ponto['pontos'][4]) ? (new DateTime($anoSelecionado . "-" . $mesSelecionado . "-" . $i . " " . $ponto['pontos'][4] . ":00"))->diff(new DateTime($anoSelecionado . "-" . $mesSelecionado . "-" . $i . " " . $ponto['pontos'][3] . ":00")) : 0;

                    /* CALCULA O TOTAL DE HORAS TRABALHADAS */
                    $totalHoras = ($primeiroTurno->h * 60 + $primeiroTurno->i) + ($segundoTurno->h * 60 + $segundoTurno->i) + ($primeiroTurnoAbono->h * 60 + $primeiroTurnoAbono->i) + ($segundoTurnoAbono->h * 60 + $segundoTurnoAbono->i);

                    $pontos[$i]['horas_trabalhadas'] = [
                        'formatada' => (intval($totalHoras / 60)) . 'h e ' . ($totalHoras % 60) . 'min',
                        'minutos' => $totalHoras
                    ];
                }
            }

            // FÉRIAS 
            $ferias = Ferias::where('data_inicio_ferias', '<=', $ultimoDiaMes)
                ->where('data_fim_ferias', '>=', $primeiroDiaMes)
                ->where('matricula_ferias', $usuario['matricula_usuario'])
                ->where('contrato_ferias', $usuario['contrato_usuario'])
                ->get();

            foreach ($ferias as $feria) {
                $diaInicial = ($feria->data_inicio_ferias <= date($anoSelecionado . '-' . $mesSelecionado . '-01')) ? '01' : date('d', strtotime($feria->data_inicio_ferias));
                $diaFinal = ($feria->data_fim_ferias >= date($anoSelecionado . '-' . $mesSelecionado . '-t')) ? date('t', strtotime($anoSelecionado . '-' . $mesSelecionado . '-01')) : date('d', strtotime($feria->data_fim_ferias));

                for ($i = intval($diaInicial); $i <= intval($diaFinal); $i++) {
                    $pontos[str_pad($i, 2, '00', STR_PAD_LEFT)]['ferias'] = 'férias';
                }
            }

            //DISPENSAS
            $dispensas = Dispensa::where('data_inicio_dispensa', '<=', $ultimoDiaMes)
                ->where('situacao_dispensa', 'S')
                ->where(function ($query) use ($primeiroDiaMes) {
                    $query->whereNull('data_fim_dispensa')->orWhere('data_fim_dispensa', '>=', $primeiroDiaMes);
                })
                ->where('id_usuario', $usuario['id_usuario'])
                ->get();
            foreach ($dispensas as $dispensa) {
                $diaInicial = ($dispensa->data_inicio_dispensa <= date($anoSelecionado . '-' . $mesSelecionado . '-01')) ? '01' : date('d', strtotime($dispensa->data_inicio_dispensa));
                $diaFinal = ($dispensa->data_fim_dispensa >= date($anoSelecionado . '-' . $mesSelecionado . '-t')) ? date('t', strtotime($anoSelecionado . '-' . $mesSelecionado . '-01')) : date('d', strtotime($dispensa->data_fim_dispensa));

                for ($i = intval($diaInicial); $i <= intval($diaFinal); $i++) {
                    $pontos[str_pad($i, 2, '00', STR_PAD_LEFT)]['dispensas'] = $dispensa->amparo_legal_dispensa;
                }
            }

            //AFASTAMENTOS
            $afastamentos = Afastamento::where('data_inicio_afastamento', '<=', $ultimoDiaMes)
                ->where('data_fim_afastamento', '>=', $primeiroDiaMes)
                ->where('matricula_afastamento', $usuario['matricula_usuario'])
                ->where('contrato_afastamento', $usuario['contrato_usuario'])
                ->get();

            foreach ($afastamentos as $afastamento) {
                $diaInicial = ($afastamento->data_inicio_afastamento <= date($anoSelecionado . '-' . $mesSelecionado . '-01')) ? '01' : date('d', strtotime($afastamento->data_inicio_afastamento));
                $diaFinal = ($afastamento->data_fim_afastamento >= date($anoSelecionado . '-' . $mesSelecionado . '-t')) ? date('t', strtotime($anoSelecionado . '-' . $mesSelecionado . '-01')) : date('d', strtotime($afastamento->data_fim_afastamento));

                for ($i = intval($diaInicial); $i <= intval($diaFinal); $i++) {
                    $pontos[str_pad($i, 2, '00', STR_PAD_LEFT)]['afastamentos'] = 'afastado / licenciado';
                }
            }

            // ESCALA
            $escalas = Escala::leftJoin('data_escala', 'data_escala.id_escala', 'escala.id_escala')
                ->where('data_escala', '>=', $primeiroDiaMes)
                ->where('data_escala', '<=', $ultimoDiaMes)
                ->where('id_usuario', $usuario['id_usuario'])
                ->get();
            foreach ($escalas as $escala) {
                $diaInicial = ($escala->data_escala <= date($anoSelecionado . '-' . $mesSelecionado . '-01')) ? '01' : date('d', strtotime($escala->data_escala));
                $diaFinal = ($escala->data_escala >= date($anoSelecionado . '-' . $mesSelecionado . '-t')) ? date('t', strtotime($anoSelecionado . '-' . $mesSelecionado . '-01')) : date('d', strtotime($escala->data_escala));

                for ($i = intval($diaInicial); $i <= intval($diaFinal); $i++) {
                    $pontos[str_pad($i, 2, '00', STR_PAD_LEFT)]['escalas'] = $escala->amparo_legal_escala;
                }
            }

            // FALTAS
            $faltas = Faltas::where('matricula_usuario', $usuario['matricula_usuario'])
                ->where('contrato', $usuario['contrato_usuario'])
                ->where('data_inicio', '<=', $ultimoDiaMes)
                ->where('data_termino', '>=', $primeiroDiaMes)
                ->orderBy('tipo', 'ASC')
                ->get();
            foreach ($faltas as $falta) {
                $diaInicial = ($falta->data_inicio <= date($anoSelecionado . '-' . $mesSelecionado . '-01')) ? '01' : date('d', strtotime($falta->data_inicio));
                $diaFinal = ($falta->data_termino >= date($anoSelecionado . '-' . $mesSelecionado . '-t')) ? date('t', strtotime($anoSelecionado . '-' . $mesSelecionado . '-01')) : date('d', strtotime($falta->data_termino));

                for ($i = intval($diaInicial); $i <= intval($diaFinal); $i++) {
                    for ($p = 1; $p <= 4; $p++) {
                        $pontos[str_pad($i, 2, '00', STR_PAD_LEFT)]['pontos'][$p] = '<span class="text-' . ($falta->tipo == 'FALTA' ? 'danger' : 'info') . '">' . $falta->tipo . '</span>';
                    }
                }
            }

            $datas = Calendario::where('data_calendario', '>=', $primeiroDiaMes)
                ->where('data_calendario', '<=', $ultimoDiaMes)
                ->get()->toArray();
            foreach ($datas as $data) {
                $pontos[date('d', strtotime($data['data_calendario']))]['datas'][] =  $data;
            }

            // echo '<pre>';
            // print_r($pontos);
            // echo '</pre>';
            // exit;

            return [
                'pontos' => $pontos,
                'lotacao' => $lotacao,
                'years' => $anos,
                'ids_horario' => array_unique($ids_horario),
                'ano' => $request->getQueryParam('mes') == 0 ? $request->getQueryParam('ano') - 1 : $request->getQueryParam('ano'),
                'mes' => $request->getQueryParam('mes') == 0 ? 12 : $request->getQueryParam('mes')
            ];
        }
    }

    public function pontos_anterior_2021($usuario, $ano, $mes)
    {

        $ultimoDiaMes = str_pad($ano, 4, '20', STR_PAD_LEFT) . "-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-31 23:59:59";
        $primeiroDiaMes = str_pad($ano, 4, '20', STR_PAD_LEFT) . "-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";
        $data = Date::create($ano, $mes);

        $feriados = Calendario::whereBetween('data_calendario', [$primeiroDiaMes, $ultimoDiaMes])->orderBy('data_calendario')->get()->toArray();

        $faltas = Faltas::where('matricula_usuario', $usuario['matricula_usuario'])
            ->where('contrato', $usuario['contrato_usuario'])
            ->whereBetween('data_inicio', [$primeiroDiaMes, $ultimoDiaMes])
            ->orderBy('tipo', 'ASC')
            ->get()->toArray();

        //CONECTA AO BANCO DE DADOS DA SEICT
        $seict = new \Illuminate\Database\Capsule\Manager;
        $seict->addConnection(DATABASE_SEICT);
        $seict->setAsGlobal();
        $seict->bootEloquent();

        $dados_servidor = (array) $seict->table('servidor')
            ->where('matricula', $usuario['matricula_usuario'])
            ->where('contrato', $usuario['contrato_usuario'])
            ->first();

        $pontos_servidor = $seict->table('ponto')
            ->where('matricula', $dados_servidor['matricula'])
            ->where('finalidade', '!=', 'HISTORICO')
            ->whereBetween('data', [$primeiroDiaMes, $ultimoDiaMes])
            ->orderBy('data')
            ->orderBy('tipo')
            ->get()
            ->toArray();

        $abonos_servidor = $seict->table('abono')
            ->where('situacao', 'A')
            ->where('matricula', $dados_servidor['matricula'])
            ->whereBetween('data', [$primeiroDiaMes, $ultimoDiaMes])
            ->orderBy('data')
            ->orderBy('periodo')
            ->orderBy('situacao')
            ->get()->toArray();

        $pontos = [];

        foreach ($pontos_servidor as $dados) {

            if ($dados->finalidade == 'FERIAS') {
                $pontos[date("d", strtotime($dados->data))]['pontos'][$dados->tipo] = 'Férias';
            } else if ($dados->finalidade == 'ABONO') {
                $pontos[date("d", strtotime($dados->data))]['pontos'][$dados->tipo] = date("H:i", strtotime($dados->data));
                $pontos[date("d", strtotime($dados->data))]['abonos'][$dados->tipo] = date("H:i", strtotime($dados->data));
            } else {
                $pontos[date("d", strtotime($dados->data))]['pontos'][$dados->tipo] = date("H:i", strtotime($dados->data));
            }
        }

        $dia_a_dia = new Recurrency();
        $dia_a_dia->startDate($data->startOfMonth())
            ->until($data->endOfMonth())
            ->freq('daily')
            ->generateOccurrences();

        foreach ($dia_a_dia->occurrences as $dia) {
            $pontos[$dia->format('d')]['dia_nome'] = Date::dayName($dia->weekDay());
            $pontos[$dia->format('d')]['fim_de_semana'] = $dia->isWeekend() ? true : false;
        }

        foreach ($abonos_servidor as $dados) {
            if ($dados->situacao == 'D') {
                $pontos[date("d", strtotime($dados->data))]['abonos'][] = $dados;
            } else {
                $pontos[date("d", strtotime($dados->data))]['solicitacoes_abono'][] = $dados;
            }
        }

        foreach ($faltas as $dados) {
            $di = Date::create($dados['data_inicio']);
            $dt = Date::create(date('Y-m-d', strtotime('+1 days', strtotime($dados['data_termino']))));

            $d = new Recurrency();
            $d->startDate($di)
                ->until($dt)
                ->freq('daily')
                ->generateOccurrences();

            foreach ($d->occurrences as $i) {
                if (!$i->isWeekend()) {
                    $pontos[$i->format('d')]['pontos'][1] = $pontos[$i->format('d')]['pontos'][2] = $pontos[$i->format('d')]['pontos'][3] = $pontos[$i->format('d')]['pontos'][4] = '<span class="text-' . ($dados['tipo'] == 'FALTA' ? 'danger' : 'info') . '">' . $dados['tipo'] . '</span>';
                }
            }
        }

        foreach ($feriados as $dados) {
            $pontos[date('d', strtotime($dados['data_calendario']))]['datas'][] = $dados;
        }

        //ORGANIZA AS DATAS NA SEQUENCIA CORRETA
        ksort($pontos);

        //RECONECTA AO BANCO DE DADOS DA SEPLAG
        $seict = new \Illuminate\Database\Capsule\Manager;
        $seict->addConnection(APP_DATABASE);
        $seict->setAsGlobal();
        $seict->bootEloquent();

        return $pontos;
    }

    public function api_index(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;

        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;
        $filtrar_orgao = ($request->getParams())['id_orgao'] != '' ? ($request->getParams())['id_orgao'] : false;
        $filtrar_lotacao = ($request->getParams())['id_lotacao'] != '' ? ($request->getParams())['id_lotacao'] : false;
        $assinatura_folha = isset(($request->getParams())['assinatura_folha']) ? ($request->getParams())['assinatura_folha'] : false;

        $order = ($request->getParams())['order'][0];

        $group_order = [
            0 => 'usuario.matricula_usuario',
            1 => 'usuario.contrato_usuario',
            2 => 'usuario.nome_usuario',
            4 => 'lotacao.descricao_lotacao',
        ];

        $ano = $request->getQueryParam('ano');
        $mes = $request->getQueryParam('mes');
        $ultimoDiaMes = str_pad($ano, 4, '20', STR_PAD_LEFT) . "-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-31 23:59:59";
        $primeiroDiaMes = str_pad($ano, 4, '20', STR_PAD_LEFT) . "-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";

        if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
            $data = Date::create($request->getQueryParam('ano'), $request->getQueryParam('mes'));

            $dia_mes_ano_inicial = ($data->startOfMonth())->toDateString();
            $dia_mes_ano_final = ($data->endOfMonth())->toDateString();

            $responsaveis = MinhasLotacoes::lotacoes();

            $mongoDB = new MongoDB();
            $mongoDB->setFilter('data_ponto', ['$gte' => $dia_mes_ano_inicial, '$lte' => $dia_mes_ano_final]);
            $mongoDB->setFilter('$or', [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']]);
            $lotacoes_ = $mongoDB->executeQuery();

            $Usuarios_Setores = [];
            $id_minhasLotacoes = [];

            foreach ($responsaveis as $chave => $dados) {
                if ($dados['status_lotacao_responsavel'] == 'A' or ($dados['data_criacao_lotacao_responsavel'] <= $ultimoDiaMes and $dados['data_atualizacao_lotacao_responsavel'] >= $primeiroDiaMes)) {
                    $id_minhasLotacoes[$chave] = $dados['id_lotacao'];
                }
            }

            foreach ($lotacoes_ as $dados) {
                foreach ($responsaveis as $chave => $responsavel) {
                    if (
                        (
                            ($responsavel['status_lotacao_responsavel'] == 'R'
                                and  $responsavel['data_atualizacao_lotacao_responsavel'] >= $primeiroDiaMes
                                and $responsavel['data_criacao_lotacao_responsavel'] <= $ultimoDiaMes
                            )
                            or $responsavel['status_lotacao_responsavel'] == 'A'
                        )
                        and $responsavel['id_lotacao'] == $dados->id_lotacao
                    ) {
                        $id_minhasLotacoes[$chave] = $chave;
                        $Usuarios_Setores[$dados->id_usuario] = $dados->id_usuario;
                    }
                }
            }
        }

        $usuarios = Usuario::select([
            'usuario.*',
            'orgao.*',
            'lotacao.*',
            (DB::raw("
                CASE WHEN (
                    SELECT  id_folha
                    FROM folha
                    WHERE folha.ano_folha = {$ano} AND folha.mes_folha = {$mes} AND folha.id_usuario = usuario.id_usuario AND folha.nome_usuario_responsavel != usuario.nome_usuario LIMIT 1
                ) IS NULL THEN ( 
                    'nao'
                ) ELSE (
                    'sim'
                ) END AS folha_assinada")),
            (DB::raw("
                (SELECT  count(1) as tt  FROM folha as f1 WHERE f1.ano_folha = {$ano} AND f1.mes_folha = {$mes} AND f1.id_usuario = usuario.id_usuario AND f1.nome_usuario_responsavel != usuario.nome_usuario) AS assinaturas")),
            (DB::raw("(
                    SELECT f2.total_assinaturas as tt
                    FROM folha as f2
                    WHERE f2.ano_folha = {$ano} AND f2.mes_folha = {$mes} AND f2.id_usuario = usuario.id_usuario AND f2.nome_usuario_responsavel != usuario.nome_usuario limit 1
                ) AS total_assinaturas")
            ),
            (DB::raw("
                CASE WHEN (
                    SELECT id_folha
                    FROM folha as sfs
                    WHERE sfs.ano_folha = {$ano} AND sfs.mes_folha = {$mes} AND sfs.id_usuario = usuario.id_usuario AND sfs.nome_usuario_responsavel = usuario.nome_usuario limit 1
                ) IS NULL THEN ( 
                    'nao'
                ) ELSE (
                    'sim'
                ) END AS assinado_servidor
            ")),
        ])
            ->join('orgao', 'usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
            ->join('lotacao', 'usuario.id_lotacao_exercicio_usuario', 'lotacao.id_lotacao')
            ->with('TipoUsuario')
            ->with('Horario')
            //->where('usuario.situacao_usuario', 'A')
            ->where(function ($query) use ($mes, $ano) {
                $query->where('usuario.situacao_usuario', 'A')
                    ->orWhereRaw("(usuario.situacao_usuario = 'D' AND usuario.data_atualizacao_usuario >= '$ano-$mes-01 00:00:00')");
            })
            ->where(function ($query) use ($Usuarios_Setores, $id_minhasLotacoes) {
                if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2) {
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                } else if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
                    $query->whereIn('usuario.id_usuario', $Usuarios_Setores)
                        ->orWhereIn('usuario.id_lotacao_exercicio_usuario', $id_minhasLotacoes);
                }
            })
            ->where(function ($query) use ($search) {
                if ($search) {
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
            ->where(function ($query) use ($assinatura_folha, $ano, $mes) {
                if ($assinatura_folha == 'sim') {
                    $query->whereIn('usuario.id_usuario', function ($query) use ($ano, $mes) {
                        $query->select('folha.id_usuario')
                            ->from(with(new Folha())->getTable())
                            ->where('ano_folha', $ano)
                            ->where('mes_folha', $mes)
                            ->whereNotNull('id_lotacao')
                            ->whereRaw('folha.id_usuario = usuario.id_usuario');
                    });
                } else if ($assinatura_folha == 'nao') {
                    $query->whereNotIn('usuario.id_usuario', function ($query) use ($ano, $mes) {
                        $query->select('folha.id_usuario')
                            ->from(with(new Folha())->getTable())
                            ->where('ano_folha', $ano)
                            ->where('mes_folha', $mes)
                            ->whereRaw('folha.id_usuario = usuario.id_usuario');
                    });
                } else if ($assinatura_folha == 'servidor') {
                    $query->whereIn('usuario.id_usuario', function ($query) use ($ano, $mes) {
                        $query->select('folha.id_usuario')
                            ->from(with(new Folha())->getTable())
                            ->where('ano_folha', $ano)
                            ->where('mes_folha', $mes)
                            ->whereNull('id_lotacao')
                            ->whereRaw('folha.id_usuario = usuario.id_usuario');
                    });
                }
            })
            //FILTRA O ORGAO
            ->where(function ($query) use ($filtrar_orgao) {
                if ($filtrar_orgao != 'A') {
                    $query->where('usuario.id_orgao_exercicio_usuario', $filtrar_orgao);
                }
            })
            //FILTRA A LOTAÇÃO
            ->where(function ($query) use ($filtrar_lotacao) {
                if ($filtrar_lotacao != 'A') {
                    $query->where('usuario.id_lotacao_exercicio_usuario', $filtrar_lotacao);
                }
            })
            ->where('usuario.id_lotacao_exercicio_usuario', '!=', '80000000001') //DESABILITA OS SERVIDOR CEDIDOS
            ->orderBy($group_order[$order['column']], $order['dir'])
            ->paginate($length, ['*'], 'page', $current_page)->toArray();



        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $usuarios['to'],
            'iTotalDisplayRecords' => $usuarios['total'],
            'aaData' => $usuarios['data'],
        ];

        return $response->withStatus(200)->withJson($resposta);
    }

    public function count(Request $request, Response $response, $args)
    {

        $ano = date('Y');
        $mes = date('m');

        $ano = $mes == 1 ? $ano - 1 : $ano;
        $mes = $mes == 1 ? 12 : $mes - 1;

        $ultimoDiaMes = str_pad($ano, 4, '20', STR_PAD_LEFT) . "-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-31 23:59:59";
        $primeiroDiaMes = str_pad($ano, 4, '20', STR_PAD_LEFT) . "-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";


        if ($request->getQueryParam('ano')) {
            $data = Date::create($request->getQueryParam('ano'), $request->getQueryParam('mes'));
        } else {
            $data = Date::create($ano, $mes);
        }

        $dia_mes_ano_inicial = ($data->startOfMonth())->toDateString();
        $dia_mes_ano_final = ($data->endOfMonth())->toDateString();

        $mongoDB = new MongoDB();
        $mongoDB->setFilter('data_ponto', ['$gte' => $dia_mes_ano_inicial, '$lte' => $dia_mes_ano_final]);
        $mongoDB->setFilter('$or', [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']]);
        $lotacoes_ = $mongoDB->executeQuery();


        if ((Auth::perfil_usuario() && (Auth::perfil_usuario())['id_tipo_perfil'] == 3)) {
            $responsaveis = MinhasLotacoes::lotacoes();
            $Usuarios_Setores = [];
            $MinhasLotacoes = [];

            foreach ($responsaveis as $responsavel) {
                if ((($responsavel['status_lotacao_responsavel'] == 'R' and  $responsavel['data_atualizacao_lotacao_responsavel'] >= $primeiroDiaMes and $responsavel['data_criacao_lotacao_responsavel'] <= $ultimoDiaMes) or $responsavel['status_lotacao_responsavel'] == 'A')) {
                    $MinhasLotacoes[] = $responsavel['id_lotacao'];
                }
            }

            foreach ($lotacoes_ as $dados) {
                foreach ($responsaveis as $responsavel) {
                    if ((($responsavel['status_lotacao_responsavel'] == 'R' and  $responsavel['data_atualizacao_lotacao_responsavel'] >= $primeiroDiaMes and $responsavel['data_criacao_lotacao_responsavel'] <= $ultimoDiaMes) or $responsavel['status_lotacao_responsavel'] == 'A') and $responsavel['id_lotacao'] == $dados->id_lotacao) {
                        $Usuarios_Setores[$dados->id_usuario] = $dados->id_usuario;
                    }
                }
            }

            $usuarios = Usuario::where(function ($query) use ($mes, $ano) {
                $query->where('usuario.situacao_usuario', 'A')
                    ->orWhereRaw("(usuario.situacao_usuario = 'D' AND usuario.data_atualizacao_usuario >= '$ano-$mes-01 00:00:00')");
            })
                ->where(function ($query) use ($Usuarios_Setores, $MinhasLotacoes) {
                    $query->whereIn('id_usuario', $Usuarios_Setores)
                        ->orWhereIn('id_lotacao_exercicio_usuario', $MinhasLotacoes);
                })
                ->where('id_orgao_exercicio_usuario', Auth::id_orgao_exercicio_usuario())
                ->where('id_lotacao_exercicio_usuario', '!=', '80000000001') //DESABILITA OS SERVIDOR SEDIDOS
                ->get();

            $folhas = Folha::whereIn('folha.id_usuario', $Usuarios_Setores)
                ->join('usuario', 'usuario.id_usuario', 'folha.id_usuario')
                ->where(function ($query) use ($Usuarios_Setores, $MinhasLotacoes) {
                    $query->orWhereIn('usuario.id_lotacao_exercicio_usuario', $MinhasLotacoes);
                })
                ->where('ano_folha', $ano)
                ->where('mes_folha', $mes)
                ->whereNotNull('id_lotacao')
                ->groupBy('usuario.id_usuario')
                ->get();

            $total = count($usuarios) - count($folhas);
        } else {
            $usuarios = Usuario::where(function ($query) use ($mes, $ano) {
                $query->where('usuario.situacao_usuario', 'A')
                    ->orWhereRaw("(usuario.situacao_usuario = 'D' AND usuario.data_atualizacao_usuario >= '$ano-$mes-01 00:00:00')");
            })
                // ->where('id_orgao_exercicio_usuario', Auth::id_orgao_exercicio_usuario())
                ->where(function ($query) {
                    if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2) {
                        $query->where('id_orgao_exercicio_usuario', Auth::id_orgao_exercicio_usuario());
                    }
                })
                ->where('id_lotacao_exercicio_usuario', '!=', '80000000001') //DESABILITA OS SERVIDOR SEDIDOS
                ->count();

            $folhas = Folha::where('ano_folha', $ano)
                ->join('usuario', 'usuario.id_usuario', 'folha.id_usuario')
                ->where('mes_folha', $mes)
                ->where(function ($query) {
                    if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2) {
                        $query->where('usuario.id_orgao_exercicio_usuario', Auth::id_orgao_exercicio_usuario());
                    }
                })
                ->whereNotNull('id_lotacao')
                // ->groupBy('id_usuario')
                ->count();

            $total = $usuarios - $folhas;
        }

        return $response->withStatus(200)->withJson($total);
    }

    public function verify(Request $request, Response $response, $args)
    {
        if ($request->getMethod() == 'POST') {
            try {

                $token = isset(($request->getParams())['token']) ? ($request->getParams())['token'] : null;

                $folha = Folha::join('usuario', 'usuario.id_usuario', 'folha.id_usuario')
                    ->where('usuario.id_lotacao_exercicio_usuario', '!=', '80000000001') //DESABILITA OS SERVIDORES CEDIDOS
                    ->where('token_folha', $token)
                    ->first();

                if (!$folha) {
                    return $response->withStatus(401)->withJson(['errorMessage' => 'Token inválido']);
                }

                return $response->withStatus(200)->withJson($folha);
            } catch (\Throwable $th) {
                return $response->withStatus(401)->withJson(['errorMessage' => 'Token inválido']);
            }
        }


        return $this->view(
            $response,
            'folhas_ponto',
            'verify',
        );
    }
}
