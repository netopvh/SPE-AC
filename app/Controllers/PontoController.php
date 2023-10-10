<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Extensions\Support\Env;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Models\{
    Usuario,
    Horario,
    HorarioPadrao
};

use App\Classes\{
    MongoDB
};

class PontoController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->view(
            $response,
            'pontos',
            'index',
            ['data_hora' => date('Y-m-d H:i:s')]
        );
    }

    public function store(Request $request, Response $response, $args)
    {
        if ($request->getMethod() == 'POST') {
            try {

                $login = ($request->getParsedBody())['email_usuario'];
                $password = ($request->getParsedBody())['password'];
                $contrato_usuario = ($request->getParsedBody())['contrato_usuario'];
                $tipo_ponto = ($request->getParsedBody())['tipo_ponto'];
                $geolocalizacao = ($request->getParsedBody())['geo'];
                $ipreal = (($request->getParsedBody())['ipreal'] != "") ? ($request->getParsedBody())['ipreal'] : null;

                if (isset(($request->getParsedBody())['tipo_ponto']) && ($request->getParsedBody())['tipo_ponto'] != 0) {

                    $usuario = Usuario::query()
                        ->with('Orgao')
                        ->with('Lotacao')
                        ->where('cpf_usuario', $login)
                        ->where(function ($query) use ($contrato_usuario) {
                            if ($contrato_usuario !== 'false') {
                                $query->where('contrato_usuario', $contrato_usuario);
                            }
                        })
                        ->where('situacao_usuario', 'A')
                        ->first();

                    if ($usuario) {

                        $usuario = $usuario->toArray();

                        $checkUser = Usuario::query()
                            ->where('nascimento', $password)
                            ->get();

                        if ($checkUser->count() > 0) {

                            $mongoDB = new MongoDB();
                            $mongoDB->setFilter('$or', [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']]);
                            $mongoDB->setFilter('data_ponto', new \MongoDB\BSON\Regex(date('Y-m-d')));
                            //$mongoDB->setFilter('tipo_ponto', (int) $tipo_ponto);
                            $mongoDB->setFilter('id_usuario', (int) $usuario['id_usuario']);
                            $mongoDB->setOptions('sort', ['tipo_ponto' => -1]);
                            $pontos = $mongoDB->executeQuery();
                            $return = [];

                            $tipo_ultimo_registro = isset($pontos[0]->tipo_ponto) ? (int) $pontos[0]->tipo_ponto : 0;

                            if (count($pontos) > 3 and $tipo_ultimo_registro == 4) {
                                return $this->respondWithError($response, [
                                    'errorMessage' => 'Você já registrou todos os horários do dia.',
                                    'tipo_ponto' => $tipo_ponto,
                                    'tipo_ultimo_registro' => $tipo_ultimo_registro
                                ]);
                            }


                            if ($tipo_ponto < $tipo_ultimo_registro) {
                                return $this->respondWithError($response, ['errorMessage' => 'Ponto já registrado, ou está fora de ordem. Solicite o abono ao seu superior!']);
                            }

                            if ($usuario['id_horario']) {
                                $horario['id_horario'] = $usuario['id_horario'];
                            } else {
                                // $horario = Horario::where(function ($query) use ($usuario){
                                //     return $query->leftJoin('horario_padrao', function ($query) use ($usuario) {
                                //         $query->on('horario_padrao.id_horario', 'horario.id_horario')
                                //         ->on('horario_padrao.status', 'S')
                                //         ->on('horario_padrao.id_orgao', $usuario['id_orgao_exercicio_usuario']);
                                //     });
                                // })->first()->toArray();


                                $horario = HorarioPadrao::where('id_orgao', $usuario['id_orgao_exercicio_usuario'])
                                    ->where('status', 'S')
                                    ->get()
                                    ->toArray()[0];
                            }

                            $mongoDB->insert([
                                'id_usuario' => (int) $usuario['id_usuario'],
                                'tipo_ponto' => (int) $tipo_ponto,
                                'data_ponto' => date('Y-m-d H:i:s'),
                                'ip_ponto' => (in_array(Env::ip(), ['10.1.9.1', '10.1.9.101', '10.1.9.102', '10.1.9.103', '10.1.9.104', '10.10.17.114', '10.10.16.1']) && $ipreal) ? $ipreal : Env::ip(),
                                'dados_ponto' => $_SERVER['HTTP_USER_AGENT'],
                                'geolocalizacao' => $geolocalizacao ?? null,
                                'matricula_usuario' => (int) $usuario['matricula_usuario'],
                                'contrato_usuario' => (int) $usuario['contrato_usuario'],
                                'cargo_usuario' => $usuario['cargo_usuario'],
                                'cargo_comissao_usuario' => $usuario['cargo_comissao_usuario'],
                                'email_usuario' => $usuario['email_usuario'],
                                'id_lotacao' => (int) $usuario['id_lotacao_exercicio_usuario'],
                                'id_orgao' => (int) $usuario['id_orgao_exercicio_usuario'],
                                'descricao_orgao' => $usuario['orgao']['descricao_orgao'],
                                'sigla_orgao' => $usuario['orgao']['sigla_orgao'],
                                'descricao_lotacao' => $usuario['lotacao']['descricao_lotacao'],
                                'nome_usuario' => $usuario['nome_usuario'],
                                'id_horario' => $horario['id_horario'],
                                'finalidade_ponto' => 'PONTO',
                                'data_criacao_ponto' => date('Y-m-d H:i:s'),
                                'data_atualizacao_ponto' => date('Y-m-d H:i:s'),
                            ]);

                            $ponto_tipo = [];
                            $pontos_formatados = [];

                            for ($i = 0; $i < count($pontos); $i++) {
                                $ponto_tipo[$pontos[$i]->tipo_ponto] = date('H:i', strtotime($pontos[$i]->data_ponto));
                            }

                            for ($i = 1; $i <= 4; $i++) {
                                $pontos_formatados[$i] = isset($ponto_tipo[$i]) ? $ponto_tipo[$i] : '-- --';
                            }

                            if ($tipo_ponto == 1) {
                                $return = [
                                    'entrada_1_ponto' => date('H:i'),
                                    'saida_1_ponto' => '-- --',
                                    'entrada_2_ponto' => '-- --',
                                    'saida_2_ponto' => '-- --',
                                    'message' => "{$usuario['nome_usuario']}<br/> Primeira Entrada Registrada com Sucesso!"
                                ];
                            } else if ($tipo_ponto == 2) {
                                $return = [
                                    'entrada_1_ponto' => $pontos_formatados[1],
                                    'saida_1_ponto' => date('H:i'),
                                    'entrada_2_ponto' => '-- --',
                                    'saida_2_ponto' => '-- --',
                                    'message' => "{$usuario['nome_usuario']}<br/> Primeira Saída Registrada com Sucesso!"
                                ];
                            } else if ($tipo_ponto == 3) {
                                $return = [
                                    'entrada_1_ponto' => $pontos_formatados[1],
                                    'saida_1_ponto' => $pontos_formatados[2],
                                    'entrada_2_ponto' => date('H:i'),
                                    'saida_2_ponto' => '-- --',
                                    'message' => "{$usuario['nome_usuario']}<br/> Segunda Entrada Registrada com Sucesso!"
                                ];
                            } else if ($tipo_ponto == 4) {
                                $return = [
                                    'entrada_1_ponto' => $pontos_formatados[1],
                                    'saida_1_ponto' => $pontos_formatados[2],
                                    'entrada_2_ponto' => $pontos_formatados[3],
                                    'saida_2_ponto' => date('H:i'),
                                    'message' => "{$usuario['nome_usuario']}<br/> Segunda Saída Registrada com Sucesso!"
                                ];
                            }

                            return $this->respondWithSuccess($response, $return);
                        } else {
                            return $this->respondWithError($response, ['errorMessage' => 'Login e/ou senha incorretos!']);
                        }
                    } else {
                        return $this->respondWithError($response, ['errorMessage' => 'Usuário não registrado em nosso banco de dados ou Usuário desativado!']);
                    }
                } else {
                    return $this->respondWithError($response, ['errorMessage' => 'Ocorreu um erro durante o processo. Tente novamente.']);
                }
            } catch (\Throwable $th) {
                return $this->respondWithError($response, ['errorMessage' => $th->getMessage()]);
            }
        }
    }

    public function api_index(Request $request, Response $response, $args)
    {
        try {

            $email_usuario = $request->getQueryParam('email_usuario') ?? '';
            $password = $request->getQueryParam('password') ?? '';
            $contrato_usuario = $request->getQueryParam('contrato_usuario') ?? null;

            $usuario = Usuario::select([
                'id_usuario',
                'id_lotacao_exercicio_usuario',
                'matricula_usuario',
                'cargo_usuario',
                'cargo_comissao_usuario'
            ])
                ->with('Lotacao.Orgao')
                ->where('cpf_usuario', $email_usuario)
                ->where('nascimento', $password)
                ->when($contrato_usuario, function ($query, $contrato_usuario) {
                    return $query->where('contrato_usuario', $contrato_usuario);
                })
                ->where('situacao_usuario', 'A')
                ->get()
                ->toArray();

            if (count($usuario) === 1) {

                $useragent = $_SERVER['HTTP_USER_AGENT'];

                if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
                    if ($usuario[0]['lotacao']['orgao']['mobile'] === 'I') {
                        return $this->respondWithError($response, ['errorMessage' => 'Acesso não permitido para o dispositivo utilizado.'], 403);
                    }
                }



                $mongoDB = new MongoDB();
                // $mongoDB->setFilter('finalidade_ponto', 'PONTO');
                $mongoDB->setFilter('$or', [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']]);
                $mongoDB->setFilter('data_ponto', new \MongoDB\BSON\Regex(date('Y-m-d')));
                $mongoDB->setFilter('id_usuario', (int) $usuario[0]['id_usuario']);
                $mongoDB->setOptions('sort', ['tipo_ponto' => -1]);
                $pontos = $mongoDB->executeQuery();

                $return = [
                    'options' => [
                        ['label' => '1ª Entrada', 'option' => 1, 'disabled' => false, 'title' => '', 'class' => 'active', 'icon' => 'login'],
                        ['label' => '1ª Saída', 'option' => 2, 'disabled' => false, 'title' => '', 'class' => 'active', 'icon' => 'logout'],
                        ['label' => '2ª Entrada', 'option' => 3, 'disabled' => false, 'title' => '', 'class' => 'active', 'icon' => 'login'],
                        ['label' => '2ª Saída', 'option' => 4, 'disabled' => false, 'title' => '', 'class' => 'active', 'icon' => 'logout']
                    ]
                ];

                $tipo_ultimo_registro = isset($pontos[0]->tipo_ponto) ? (int) $pontos[0]->tipo_ponto : 0;

                if ($tipo_ultimo_registro == 1) {
                    $return['options'][0] = ['label' => '1ª Entrada', 'option' => 1, 'disabled' => true, 'title' => 'Ponto já Registrado ou Ascendido', 'class' => '', 'icon' => 'login'];
                } else if ($tipo_ultimo_registro == 2) {
                    $return['options'][0] = ['label' => '1ª Entrada', 'option' => 1, 'disabled' => true, 'title' => 'Ponto já Registrado ou Ascendido', 'class' => '', 'icon' => 'login'];
                    $return['options'][1] = ['label' => '1ª Saída', 'option' => 2, 'disabled' => true, 'title' => 'Ponto já Registrado ou Ascendido', 'class' => '', 'icon' => 'logout'];
                } else if ($tipo_ultimo_registro == 3) {
                    $return['options'][0] = ['label' => '1ª Entrada', 'option' => 1, 'disabled' => true, 'title' => 'Ponto já Registrado ou Ascendido', 'class' => '', 'icon' => 'login'];
                    $return['options'][1] = ['label' => '1ª Saída', 'option' => 2, 'disabled' => true, 'title' => 'Ponto já Registrado ou Ascendido', 'class' => '', 'icon' => 'logout'];
                    $return['options'][2] = ['label' => '2ª Entrada', 'option' => 3, 'disabled' => true, 'title' => 'Ponto já Registrado ou Ascendido', 'class' => '', 'icon' => 'login'];
                } else if ($tipo_ultimo_registro == 4) {
                    return $this->respondWithError($response, ['errorMessage' => 'Você já registrou todos os horários do dia.']);
                }

                return $this->respondWithSuccess($response, $return);
            } else if (count($usuario) == 0) {
                return $this->respondWithError($response, ['errorMessage' => 'Usuário não registrado em nosso banco de dados ou dados inválidos!']);
            }

            return $this->respondWithSuccess($response, ['contratos' => $usuario]);
        } catch (\Throwable $th) {
            return $this->respondWithError($response, ['errorMessage' => $th->getMessage()]);
        }
    }
}
