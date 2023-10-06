<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Extensions\Support\Date;
use App\Extensions\Support\Recurrency;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Extensions\Support\Hash;

use App\Extensions\Support\FileSystem;
use Slim\Views\Twig;

use App\Utils\Auth;

use App\Models\{
    Usuario,
    Ponto,
    Calendario,
    Ferias,
    Horario,
    Lotacao,
    OrgaoResponsavel,
    LotacaoResponsavel,
    PerfilUsuario,
    Dispensa,
    Escala,
    DataEscala,
    Folha,
    Abono,
    Relatorio,
    Orgao
};

use Dompdf\Dompdf;
use Knp\Snappy\Pdf;

use DateTime;

use App\Classes\{
    MongoDB,
    LDAP
};

use MongoDB\BSON\Regex;

class RelatorioController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->view(
            $response,
            'relatorios',
            'index',
        );
    }

    public function show(Request $request, Response $response, $args)
    {
        $relatorio = Relatorio::where('link_relatorio', $args['link_relatorio'])->first();

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;
        $situacao = $request->getQueryParam('situacao') ? $request->getQueryParam('situacao') : null;
        $id_usuario = $request->getQueryParam('id_usuario') ? $request->getQueryParam('id_usuario') : null;
        $situacao_funcional_usuario = $request->getQueryParam('situacao_funcional_usuario') ?? null;

        $orgaos = Orgao::where(function ($query) {
            if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2 || (Auth::perfil_usuario())['id_tipo_perfil'] == 4) {
                $query->whereIn('id_orgao',  function ($query) {
                    $query->select('id_orgao')
                        ->from(with(new OrgaoResponsavel())->getTable())
                        ->where('id_usuario', Auth::id_usuario());
                });
            }
        })
            ->orderBy('sigla_orgao')
            ->get()->toArray();

        $lotacoes = Lotacao::where(function ($query) use ($id_orgao) {
            if ($id_orgao) {
                $query->where('id_orgao', $id_orgao);
            }
        })
            ->where(function ($query) {
                if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2 || (Auth::perfil_usuario())['id_tipo_perfil'] == 4) {
                    $query->whereIn('id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                }
            })
            ->where('status_lotacao', 'A')
            ->orderBy('descricao_lotacao')
            ->get()->toArray();

        $anos = [];
        for ($i = 2019; $i <= date('Y'); $i++) {
            $anos[] = $i;
        }

        if ($id_usuario) {
            $usuario = Usuario::join('orgao', 'orgao.id_orgao', 'usuario.id_orgao_exercicio_usuario')
                ->join('lotacao', 'lotacao.id_lotacao', 'usuario.id_lotacao_exercicio_usuario')
                ->with('TipoUsuario')
                ->find($id_usuario)->toArray();

            return $this->view(
                $response,
                'relatorios',
                'show/' . $relatorio->link_relatorio . '/show',
                [
                    'relatorio' => $relatorio,
                    'years' => $anos,
                    'ano' => $request->getQueryParam('ano'),
                    'mes' => $request->getQueryParam('mes'),
                    'qtd' => $request->getQueryParam('qtd') ?? 4,
                    'orgaos' => $orgaos,
                    'lotacoes' => $lotacoes,
                    'id_orgao' => $id_orgao,
                    'id_lotacao' => $id_lotacao,
                    'situacao' => $situacao,
                    'usuario' => $usuario,
                    'situacao_funcional_usuario' => $situacao_funcional_usuario
                ]
            );
        }


        return $this->view(
            $response,
            'relatorios',
            'show/' . $relatorio->link_relatorio . '/index',
            [
                'relatorio' => $relatorio,
                'years' => $anos,
                'ano' => $request->getQueryParam('ano') ? $request->getQueryParam('ano') : date('Y'),
                'mes' => $request->getQueryParam('mes') ? $request->getQueryParam('mes') : date('m'),
                'qtd' => $request->getQueryParam('qtd') ?? 4,
                'orgaos' => $orgaos,
                'lotacoes' => $lotacoes,
                'id_orgao' => $id_orgao,
                'id_lotacao' => $id_lotacao,
                'situacao' => $situacao,
                'situacao_funcional_usuario' => $situacao_funcional_usuario
            ]
        );
    }

    public function print(Request $request, Response $response, $args)
    {
        if ($args['link_relatorio'] == 'sem_registro') {
            return $this->print_sem_registro($request, $response, $args);
        } else if ($args['link_relatorio'] == 'dispensados') {
            return $this->print_dispensados($request, $response, $args);
        } else if ($args['link_relatorio'] == 'plataforma') {
            return $this->print_plataforma($request, $response, $args);
        } else if ($args['link_relatorio'] == 'ip') {
            return $this->print_ip($request, $response, $args);
        } else if ($args['link_relatorio'] == 'horas') {
            return $this->print_horas($request, $response, $args);
        } else if ($args['link_relatorio'] == 'escalas') {
            return $this->print_escalas($request, $response, $args);
        }
    }

    public function api_relatorio(Request $request, Response $response, $args)
    {
        if ($args['link_relatorio'] == 'sem_registro') {
            return $this->api_sem_registro($request, $response, $args);
        } else if ($args['link_relatorio'] == 'dispensados') {
            return $this->api_dispensados($request, $response, $args);
        } else if ($args['link_relatorio'] == 'plataforma') {
            if ($request->getQueryParam('id_usuario')) {
                return $this->api_plataforma_usuario($request, $response, $args);
            }
            return $this->api_plataforma($request, $response, $args);
        } else if ($args['link_relatorio'] == 'ip') {
            return $this->api_por_ip($request, $response, $args);
        } else if ($args['link_relatorio'] == 'horas') {
            return $this->api_horas($request, $response, $args);
        } else if ($args['link_relatorio'] == 'escalas') {
            return $this->api_escalas($request, $response, $args);
        } else if ($args['link_relatorio'] == 'auditoria') {
            return $this->api_auditoria($request, $response, $args);
        }
    }

    public function api_index(Request $request, Response $response, $args)
    {
        $relatorios = Relatorio::where('situacao_relatorio', 'S')
            ->orderBy('descricao_relatorio')
            ->paginate()->toArray();

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $relatorios['to'],
            'iTotalDisplayRecords' => $relatorios['total'],
            'aaData' => $relatorios['data'],
        ];

        return $response->withStatus(200)->withJson($resposta);
    }

    // Relatório: Sem Registro 

    public function print_sem_registro(Request $request, Response $response, $args)
    {
        $relatorio = Relatorio::where('link_relatorio', $args['link_relatorio'])->first();

        $data = Date::create($request->getQueryParam('ano'), $request->getQueryParam('mes'));

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;
        $situacao_funcional_usuario = $request->getQueryParam('situacao_funcional_usuario') ?? null;
        $acao = $request->getQueryParam('acao') ?? 'print';

        $data_inicial = $data->startOfMonth()->toDateString();
        $data_final = $data->endOfMonth()->toDateString();

        $mongoDB = new MongoDB();
        $mongoDB->setFilter('$or', [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']]);
        $mongoDB->setFilter('data_ponto', ['$gte' => $data_inicial . ' 00:00:00', '$lte' => $data_final . ' 23:59:59']);

        if ($id_orgao) {
            $mongoDB->setFilter('id_orgao', intval($id_orgao));
        }

        if ($id_lotacao) {
            $mongoDB->setFilter('id_lotacao', intval($id_lotacao));
        }

        $pontos = $mongoDB->executeQuery();

        $ids_usuario = [];

        foreach ($pontos as $ponto) {
            $ids_usuario[] = $ponto->id_usuario;
        }

        $ids_usuario = array_unique($ids_usuario);

        $usuarios = Usuario::join('orgao', 'usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
            ->join('lotacao', 'usuario.id_lotacao_exercicio_usuario', 'lotacao.id_lotacao')
            ->where('usuario.situacao_usuario', 'A')
            ->whereNotIn('usuario.id_usuario', $ids_usuario)
            ->where(function ($query) {
                if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2 || (Auth::perfil_usuario())['id_tipo_perfil'] == 4) {
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                } else if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
                    $query->whereIn('lotacao.id_lotacao',  function ($query) {
                        $query->select('id_lotacao')
                            ->from(with(new LotacaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                }
            })
            ->where(function ($query) use ($id_orgao) {
                if ($id_orgao) {
                    $query->where('orgao.id_orgao', $id_orgao);
                }
            })
            ->where(function ($query) use ($id_lotacao) {
                if ($id_lotacao) {
                    $query->where('lotacao.id_lotacao', $id_lotacao);
                }
            })
            ->where(function ($query) use ($data_inicial, $data_final) {
                $query->whereNotIn('usuario.id_usuario',  function ($query) use ($data_inicial, $data_final) {
                    $query->select('id_usuario')
                        ->from(with(new Dispensa())->getTable())
                        ->where('situacao_dispensa', 'S')

                        ->where(function ($query) use ($data_final) {
                            $query->whereNull('dispensa.data_fim_dispensa')
                                ->orWhere('dispensa.data_fim_dispensa', '>', $data_final);
                        });

                    // ->orWhereBetween('dispensa.data_fim_dispensa',[$data_inicial, $data_final])

                });
            })
            ->where(function ($query) use ($situacao_funcional_usuario) {
                if ($situacao_funcional_usuario) {
                    $query->where('situacao_funcional_usuario', $situacao_funcional_usuario);
                }
            })
            ->select('orgao.*', 'lotacao.*', 'usuario.*');

        if ($acao == 'print') {

            $usuarios = $usuarios->get()->toArray();
            return $this->view(
                $response,
                'relatorios',
                'show/sem_registro/print',
                [
                    'relatorio' => $relatorio,
                    'usuarios' => $usuarios,
                    'situacao_funcional_usuario' => $situacao_funcional_usuario,
                    'ano' => $request->getQueryParam('ano'),
                    'mes' => $request->getQueryParam('mes'),
                ]
            );
        }


        $usuarios = $usuarios->get()->toArray();


        $orgaos = [];

        foreach ($usuarios as $ctg => $usuario) {

            if (!$orgaos[$usuario['id_orgao']]) {
                $orgaos[$usuario['id_orgao']] = [
                    'id_orgao' => $usuario['id_orgao'],
                    'descricao_orgao' => $usuario['descricao_orgao'],
                    'sigla_orgao' => $usuario['sigla_orgao'],
                    'usuarios' => []
                ];
            }

            $orgaos[$usuario['id_orgao']]['usuarios'][] = [
                'seg' => $ctg,
                'matricula_usuario' => $usuario['matricula_usuario'],
                'contrato_usuario' => $usuario['contrato_usuario'],
                'nome_usuario' => $usuario['nome_usuario'],
                'descricao_lotacao' => $usuario['descricao_lotacao'],
            ];
        }

        // return $this->view(
        // $response,
        // 'relatorios',
        // 'show/sem_registro/print',
        // [ 
        // 'orgaos' => $orgaos,
        // 'relatorio' => $relatorio,
        // 'usuarios' => $usuarios,
        // 'situacao_funcional_usuario' => $situacao_funcional_usuario,
        // 'ano' => $request->getQueryParam('ano'),
        // 'mes' => $request->getQueryParam('mes'),
        // ]
        // );

        $twig = new Twig(
            Filesystem::directory('~/resources/views'),
            ['cache' => false]
        );

        $html = $twig->fetch(
            'pages/relatorios/show/sem_registro/pdf.twig',
            [
                'APP_URL' => 'http:' . APP_URL,
                'orgaos' => $orgaos,
                'relatorio' => $relatorio,
                'usuarios' => $usuarios,
                'situacao_funcional_usuario' => $situacao_funcional_usuario,
                'ano' => $request->getQueryParam('ano'),
                'mes' => $request->getQueryParam('mes'),
            ]
        );

        $snappy = new Pdf('/usr/local/bin/wkhtmltopdf');

        $snappy->setOptions([
            'encoding' => 'UTF-8',
            'margin-bottom' => 6,
            'margin-left' => 5,
            'margin-right' => 5,
            'margin-top' => 5,

            'footer-font-size'  => 10,
            'footer-font-name' => 'Arial',
        ]);

        $snappy->setOption('footer-html', 'Relat&oacute;rio gerado em  ' . date('d/m/Y') . ' &agrave;s ' . date('H:i') . '');
        $snappy->setOption('footer-right', '[page] de [topage]');

        $time = time();
        $snappy->generateFromHtml($html, '/tmp/relatorio-' . $time . '.pdf');


        header('Content-Type: application/pdf');
        echo file_get_contents('/tmp/relatorio-' . $time . '.pdf');

        exit;
    }

    public function pdf_sem_registro(Request $request, Response $response, $args)
    {
    }

    public function api_sem_registro(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;

        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;

        $order = isset(($request->getParams())['order']) ? ($request->getParams())['order'][0] : null;

        $group_order = [
            0 => 'usuario.matricula_usuario',
            1 => 'usuario.contrato_usuario',
            2 => 'usuario.nome_usuario',
            4 => 'orgao.sigla_orgao',
            5 => 'lotacao.descricao_lotacao',
        ];

        if (!$order) {
            $order['column'] = 2;
            $order['dir'] = 'asc';
        }

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;
        $situacao_funcional_usuario = $request->getQueryParam('situacao_funcional_usuario') ?? null;

        $pontos = [];
        $data = Date::create($request->getQueryParam('ano'), $request->getQueryParam('mes'));

        $data_inicial = $data->startOfMonth()->toDateString();
        $data_final = $data->endOfMonth()->toDateString();

        $mongoDB = new MongoDB();
        $mongoDB->setFilter('$or', [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']]);
        $mongoDB->setFilter('data_ponto', ['$gte' => $data_inicial . ' 00:00:00', '$lte' => $data_final . ' 23:59:59']);

        if ($id_orgao) {
            $mongoDB->setFilter('id_orgao', intval($id_orgao));
        }

        if ($id_lotacao) {
            $mongoDB->setFilter('id_lotacao', intval($id_lotacao));
        }

        $pontos = $mongoDB->executeQuery();

        $ids_usuario = [];

        foreach ($pontos as $ponto) {
            $ids_usuario[] = $ponto->id_usuario;
        }

        $ids_usuario = array_unique($ids_usuario);


        $usuarios = Usuario::join('orgao', 'usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
            ->join('lotacao', 'usuario.id_lotacao_exercicio_usuario', 'lotacao.id_lotacao')
            ->where('usuario.situacao_usuario', 'A')
            ->whereNotIn('usuario.id_usuario', $ids_usuario)
            ->where(function ($query) {
                if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2 || (Auth::perfil_usuario())['id_tipo_perfil'] == 4) {
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                } else if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
                    $query->whereIn('lotacao.id_lotacao',  function ($query) {
                        $query->select('id_lotacao')
                            ->from(with(new LotacaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                }
            })
            ->where(function ($query) use ($id_orgao) {
                if ($id_orgao) {
                    $query->where('orgao.id_orgao', $id_orgao);
                }
            })
            ->where(function ($query) use ($id_lotacao) {
                if ($id_lotacao) {
                    $query->where('lotacao.id_lotacao', $id_lotacao);
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
            ->where(function ($query) use ($data_inicial, $data_final) {
                $query->whereNotIn('usuario.id_usuario',  function ($query) use ($data_inicial, $data_final) {
                    $query->select('id_usuario')
                        ->from(with(new Dispensa())->getTable())
                        ->where('situacao_dispensa', 'S')

                        ->where(function ($query) use ($data_final) {
                            $query->whereNull('dispensa.data_fim_dispensa')
                                ->orWhere('dispensa.data_fim_dispensa', '>', $data_final);
                        });

                    // ->orWhereBetween('dispensa.data_fim_dispensa',[$data_inicial, $data_final])

                });
            })
            ->where(function ($query) use ($situacao_funcional_usuario) {
                if ($situacao_funcional_usuario) {
                    $query->where('situacao_funcional_usuario', $situacao_funcional_usuario);
                }
            })
            ->orderBy($group_order[$order['column']], $order['dir'])
            ->paginate($length, ['*'], 'page', $current_page)->toArray();

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => $usuarios['to'],
            'iTotalDisplayRecords' => $usuarios['total'],
            'aaData' => $usuarios['data'],
            'ids_usuario' => $pontos,
        ];

        return $response->withStatus(200)->withJson($resposta);
    }

    // Relatório: Dispensados 

    public function print_dispensados(Request $request, Response $response, $args)
    {
        $relatorio = Relatorio::where('link_relatorio', $args['link_relatorio'])->first();

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;
        $situacao = $request->getQueryParam('situacao') ? $request->getQueryParam('situacao') : null;

        $usuarios = Dispensa::select([
            'dispensa.*',
            'usuario.*',
            'orgao.*',
            'lotacao.*',
            DB::raw("CASE WHEN ISNULL (dispensa.data_fim_dispensa) OR dispensa.data_fim_dispensa > CURRENT_DATE THEN 'ABERTA'
                WHEN dispensa.data_fim_dispensa < CURRENT_DATE THEN 'FINALIZADA'
                END AS situacao")
        ])
            ->join('usuario', 'usuario.id_usuario', 'dispensa.id_usuario')
            ->join('orgao', 'orgao.id_orgao', 'usuario.id_orgao_exercicio_usuario')
            ->join('lotacao', 'lotacao.id_lotacao', 'usuario.id_lotacao_exercicio_usuario')
            ->where('situacao_dispensa', 'S')
            ->where(function ($query) {
                if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2 || (Auth::perfil_usuario())['id_tipo_perfil'] == 4) {
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                } else if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
                    $query->whereIn('lotacao.id_lotacao',  function ($query) {
                        $query->select('id_lotacao')
                            ->from(with(new LotacaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                }
            })
            ->where(function ($query) use ($id_orgao) {
                if ($id_orgao) {
                    $query->where('orgao.id_orgao', intval($id_orgao));
                }
            })
            ->where(function ($query) use ($id_lotacao) {
                if ($id_lotacao) {
                    $query->where('lotacao.id_lotacao', intval($id_lotacao));
                }
            })
            ->where(function ($query) use ($situacao) {
                if ($situacao) {
                    if ($situacao == 'finalizada') {
                        return $query->whereRaw('dispensa.data_fim_dispensa <= CURRENT_DATE');
                    } else {
                        return $query->whereNull('dispensa.data_fim_dispensa')->orWhereRaw('dispensa.data_fim_dispensa > CURRENT_DATE');
                    }
                }
            })
            ->orderBy('orgao.sigla_orgao')
            ->orderBy('usuario.nome_usuario')
            ->get()->toArray();

        return $this->view(
            $response,
            'relatorios',
            'show/dispensados/print',
            [
                'relatorio' => $relatorio,
                'usuarios' => $usuarios,
                'ano' => $request->getQueryParam('ano'),
                'mes' => $request->getQueryParam('mes'),
            ]
        );
    }

    public function api_dispensados(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;

        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;

        $order = isset(($request->getParams())['order']) ? ($request->getParams())['order'][0] : null;

        $group_order = [
            0 => 'usuario.matricula_usuario',
            1 => 'usuario.contrato_usuario',
            2 => 'usuario.nome_usuario',
            4 => 'orgao.sigla_orgao',
            5 => 'lotacao.descricao_lotacao',
        ];

        if (!$order) {
            $order['column'] = 2;
            $order['dir'] = 'asc';
        }

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;
        $situacao = $request->getQueryParam('situacao') ? $request->getQueryParam('situacao') : null;

        $pontos = [];

        $usuarios = Dispensa::select([
            'dispensa.*',
            'usuario.*',
            'orgao.*',
            'lotacao.*',
            DB::raw("CASE WHEN ISNULL (dispensa.data_fim_dispensa) OR dispensa.data_fim_dispensa > CURRENT_DATE THEN 'ABERTA'
                WHEN dispensa.data_fim_dispensa < CURRENT_DATE THEN 'FINALIZADA'
                END AS situacao")
        ])
            ->join('usuario', 'usuario.id_usuario', 'dispensa.id_usuario')
            ->join('orgao', 'orgao.id_orgao', 'usuario.id_orgao_exercicio_usuario')
            ->join('lotacao', 'lotacao.id_lotacao', 'usuario.id_lotacao_exercicio_usuario')
            ->where('situacao_dispensa', 'S')
            ->where(function ($query) {
                if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2 || (Auth::perfil_usuario())['id_tipo_perfil'] == 4) {
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                } else if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
                    $query->whereIn('lotacao.id_lotacao',  function ($query) {
                        $query->select('id_lotacao')
                            ->from(with(new LotacaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
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
            ->where(function ($query) use ($id_orgao) {
                if ($id_orgao) {
                    $query->where('orgao.id_orgao', $id_orgao);
                }
            })
            ->where(function ($query) use ($id_lotacao) {
                if ($id_lotacao) {
                    $query->where('lotacao.id_lotacao', $id_lotacao);
                }
            })
            ->where(function ($query) use ($situacao) {
                if ($situacao) {
                    if ($situacao == 'aberta') {
                        return $query->whereNull('dispensa.data_fim_dispensa')->orWhereRaw('dispensa.data_fim_dispensa > CURRENT_DATE');
                    } else {
                        return $query->whereRaw('dispensa.data_fim_dispensa <= CURRENT_DATE');
                    }
                }
            })
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

    // Relatório: Plataforma

    public function print_plataforma(Request $request, Response $response, $args)
    {
        $relatorio = Relatorio::where('link_relatorio', $args['link_relatorio'])->first();

        $data = Date::create($request->getQueryParam('ano'), $request->getQueryParam('mes'));

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;

        $data_inicial = $data->startOfMonth()->toDateString();
        $data_final = $data->endOfMonth()->toDateString();

        $mongoDB = new MongoDB();
        $mongoDB->setFilter('$or', [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']]);
        $mongoDB->setFilter('data_ponto', ['$gte' => $data_inicial . ' 00:00:00', '$lte' => $data_final . ' 23:59:59']);
        $mongoDB->setFilter('$or', [
            ['dados_ponto' => new Regex('iPhone')],
            ['dados_ponto' => new Regex('iPad')],
            ['dados_ponto' => new Regex('Android')],
            ['dados_ponto' => new Regex('webOS')],
            ['dados_ponto' => new Regex('BlackBerry')],
            ['dados_ponto' => new Regex('iPod')],
            ['dados_ponto' => new Regex('Symbian')],
            ['dados_ponto' => new Regex('Windows Phone')]
        ]);
        $pontos = $mongoDB->executeQuery();

        $ids_usuario = [];

        foreach ($pontos as $ponto) {
            $ids_usuario[] = $ponto->id_usuario;
        }

        $usuarios = Usuario::join('orgao', 'usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
            ->join('lotacao', 'usuario.id_lotacao_exercicio_usuario', 'lotacao.id_lotacao')
            ->where('usuario.situacao_usuario', 'A')
            ->whereIn('usuario.id_usuario', $ids_usuario)
            ->where(function ($query) {
                if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2 || (Auth::perfil_usuario())['id_tipo_perfil'] == 4) {
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                } else if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
                    $query->whereIn('lotacao.id_lotacao',  function ($query) {
                        $query->select('id_lotacao')
                            ->from(with(new LotacaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                }
            })
            ->where(function ($query) use ($id_orgao) {
                if ($id_orgao) {
                    $query->where('orgao.id_orgao', $id_orgao);
                }
            })
            ->where(function ($query) use ($id_lotacao) {
                if ($id_lotacao) {
                    $query->where('lotacao.id_lotacao', $id_lotacao);
                }
            })
            ->orderBy('usuario.nome_usuario')
            ->get()->toArray();

        return $this->view(
            $response,
            'relatorios',
            'show/plataforma/print',
            [
                'relatorio' => $relatorio,
                'usuarios' => $usuarios,
                'ano' => $request->getQueryParam('ano'),
                'mes' => $request->getQueryParam('mes'),
            ]
        );
    }

    public function api_plataforma(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;

        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;

        $order = isset(($request->getParams())['order']) ? ($request->getParams())['order'][0] : null;

        $group_order = [
            0 => 'usuario.matricula_usuario',
            1 => 'usuario.contrato_usuario',
            2 => 'usuario.nome_usuario',
            4 => 'orgao.sigla_orgao',
            5 => 'lotacao.descricao_lotacao',
        ];

        if (!$order) {
            $order['column'] = 2;
            $order['dir'] = 'asc';
        }

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;

        $pontos = [];
        $data = Date::create($request->getQueryParam('ano'), $request->getQueryParam('mes'));

        $data_inicial = $data->startOfMonth()->toDateString();
        $data_final = $data->endOfMonth()->toDateString();

        $mongoDB = new MongoDB();
        $mongoDB->setFilter('$or', [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']]);
        $mongoDB->setFilter('data_ponto', ['$gte' => $data_inicial . ' 00:00:00', '$lte' => $data_final . ' 23:59:59']);
        $mongoDB->setFilter('$or', [
            ['dados_ponto' => new Regex('iPhone')],
            ['dados_ponto' => new Regex('iPad')],
            ['dados_ponto' => new Regex('Android')],
            ['dados_ponto' => new Regex('webOS')],
            ['dados_ponto' => new Regex('BlackBerry')],
            ['dados_ponto' => new Regex('iPod')],
            ['dados_ponto' => new Regex('Symbian')],
            ['dados_ponto' => new Regex('Windows Phone')]
        ]);
        $pontos = $mongoDB->executeQuery();

        $ids_usuario = [];

        foreach ($pontos as $ponto) {
            $ids_usuario[] = $ponto->id_usuario;
        }

        $usuarios = Usuario::join('orgao', 'usuario.id_orgao_exercicio_usuario', 'orgao.id_orgao')
            ->join('lotacao', 'usuario.id_lotacao_exercicio_usuario', 'lotacao.id_lotacao')
            ->where('usuario.situacao_usuario', 'A')
            ->whereIn('usuario.id_usuario', $ids_usuario)
            ->where(function ($query) {
                if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2 || (Auth::perfil_usuario())['id_tipo_perfil'] == 4) {
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                } else if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
                    $query->whereIn('lotacao.id_lotacao',  function ($query) {
                        $query->select('id_lotacao')
                            ->from(with(new LotacaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                }
            })
            ->where(function ($query) use ($id_orgao) {
                if ($id_orgao) {
                    $query->where('orgao.id_orgao', $id_orgao);
                }
            })
            ->where(function ($query) use ($id_lotacao) {
                if ($id_lotacao) {
                    $query->where('lotacao.id_lotacao', $id_lotacao);
                }
            })
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

    public function api_plataforma_usuario(Request $request, Response $response, $args)
    {
        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;
        $id_usuario = $request->getQueryParam('id_usuario') ? $request->getQueryParam('id_usuario') : null;

        $data = Date::create($request->getQueryParam('ano'), $request->getQueryParam('mes'));

        $data_inicial = $data->startOfMonth()->toDateString();
        $data_final = $data->endOfMonth()->toDateString();

        $mongoDB = new MongoDB();
        $mongoDB->setFilter('$or', [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']]);
        $mongoDB->setFilter('data_ponto', ['$gte' => $data_inicial . ' 00:00:00', '$lte' => $data_final . ' 23:59:59']);
        $mongoDB->setFilter('id_usuario', (int) $id_usuario);
        $mongoDB->setFilter('$or', [
            ['dados_ponto' => new Regex('iPhone')],
            ['dados_ponto' => new Regex('iPad')],
            ['dados_ponto' => new Regex('Android')],
            ['dados_ponto' => new Regex('webOS')],
            ['dados_ponto' => new Regex('BlackBerry')],
            ['dados_ponto' => new Regex('iPod')],
            ['dados_ponto' => new Regex('Symbian')],
            ['dados_ponto' => new Regex('Windows Phone')]
        ]);

        $pontos = $mongoDB->executeQuery();

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => 1,
            'iTotalDisplayRecords' => count($pontos),
            'aaData' => $pontos,
        ];

        return $response->withStatus(200)->withJson($resposta);
    }

    // Relatório: Por IP
    public function print_ip(Request $request, Response $response, $args)
    {
        $relatorio = Relatorio::where('link_relatorio', $args['link_relatorio'])->first();

        $search = $request->getParams()['search'] ? $request->getParams()['search'] : false;

        // $qtd_aceita = $request->getParams()['qtd_aceita'] ? $request->getParams()['qtd_aceita'] : 4;

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;
        $qtd_aceita = $request->getQueryParam('qtd');

        $pontos = [];
        $data = Date::create($request->getQueryParam('ano'), $request->getQueryParam('mes'));

        $data_inicial = $data->startOfMonth()->toDateString();
        $data_final = $data->endOfMonth()->toDateString();

        $mongoDB = new MongoDB();
        if ((Auth::perfil_usuario())['id_tipo_perfil'] <> 1) {
            $mongoDB->setFilter('id_orgao', intval(Auth::id_orgao_exercicio_usuario()));
        }


        if ($id_orgao) {
            $mongoDB->setFilter('id_orgao', intval($id_orgao));
        }

        if ($id_lotacao) {
            $mongoDB->setFilter('id_lotacao', intval($id_lotacao));
        }

        $mongoDB->setFilter('finalidade_ponto', 'PONTO');
        $mongoDB->setFilter('data_ponto', ['$gte' => $data_inicial . ' 00:00:00', '$lte' => $data_final . ' 23:59:59']);
        $mongoDB->setFilter('$or', [
            ['ip_ponto' => new Regex($search)],
            ['matricula_usuario' => new Regex($search)],
            ['nome_usuario' => new Regex($search)],
        ]);
        $pontos = $mongoDB->executeQuery();

        $ips_ponto = [];
        $ips_listados = [];

        foreach ($pontos as $ponto) {
            $ips_ponto[date('d', strtotime($ponto->data_ponto))][$ponto->ip_ponto]['servidores'][] = trim($ponto->nome_usuario);
            $ips_ponto[date('d', strtotime($ponto->data_ponto))][$ponto->ip_ponto]['lotacoes'][] = trim($ponto->descricao_lotacao);
            $ips_ponto[date('d', strtotime($ponto->data_ponto))][$ponto->ip_ponto]['orgaos'][] = trim($ponto->sigla_orgao);
        }

        foreach ($ips_ponto as $dia => $dados) {
            foreach ($dados as $ip => $users) {
                if (count($users['servidores']) > $qtd_aceita) {
                    $ips_listados[] = [
                        'dia' => $dia,
                        'ip' => $ip,
                        'total' => count($users['servidores']),
                        'servidores' => implode(", ", array_unique($users['servidores'])),
                        'lotacoes' => implode(", ", array_unique($users['lotacoes'])),
                        'orgao' => implode(", ", array_unique($users['orgaos']))
                    ];
                }
            }
        }

        return $this->view(
            $response,
            'relatorios',
            'show/ip/print',
            [
                'relatorio' => $relatorio,
                'usuarios' => $ips_listados,
                'ano' => $request->getQueryParam('ano'),
                'mes' => $request->getQueryParam('mes'),
                'qtd' => $request->getQueryParam('qtd') ?? 4,
            ]
        );
    }

    public function api_por_ip(Request $request, Response $response, $args)
    {

        $search = $request->getParams()['search'] ? $request->getParams()['search'] : false;

        $qtd_aceita = $request->getParams()['qtd_aceita'];

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;

        $pontos = [];
        $data = Date::create($request->getQueryParam('ano'), $request->getQueryParam('mes'));

        $data_inicial = $data->startOfMonth()->toDateString();
        $data_final = $data->endOfMonth()->toDateString();

        $mongoDB = new MongoDB();
        if ((Auth::perfil_usuario())['id_tipo_perfil'] <> 1) {
            $orgao = Orgao::find(Auth::id_orgao_exercicio_usuario());
            $mongoDB->setFilter('sigla_orgao', new Regex($orgao->sigla_orgao));
        }

        if ($id_orgao) {
            $mongoDB->setFilter('id_orgao', intval($id_orgao));
        }

        if ($id_lotacao) {
            $mongoDB->setFilter('id_lotacao', intval($id_lotacao));
        }

        $mongoDB->setFilter('finalidade_ponto', 'PONTO');
        $mongoDB->setFilter('data_ponto', ['$gte' => $data_inicial . ' 00:00:00', '$lte' => $data_final . ' 23:59:59']);
        $mongoDB->setFilter('$or', [
            ['ip_ponto' => new Regex($search)],
            ['matricula_usuario' => new Regex($search)],
            ['nome_usuario' => new Regex($search)],
        ]);
        $pontos = $mongoDB->executeQuery();

        $ips_ponto = [];
        $ips_listados = [];

        foreach ($pontos as $ponto) {
            $ips_ponto[date('d', strtotime($ponto->data_ponto))][$ponto->ip_ponto]['servidores'][] = trim($ponto->nome_usuario);
            $ips_ponto[date('d', strtotime($ponto->data_ponto))][$ponto->ip_ponto]['lotacoes'][] = trim($ponto->descricao_lotacao);
            $ips_ponto[date('d', strtotime($ponto->data_ponto))][$ponto->ip_ponto]['orgaos'][] = trim($ponto->sigla_orgao);
        }

        foreach ($ips_ponto as $dia => $dados) {
            foreach ($dados as $ip => $users) {
                if (count($users['servidores']) > $qtd_aceita) {
                    $ips_listados[] = [
                        'dia' => $dia,
                        'ip' => $ip,
                        'total' => count($users['servidores']),
                        'servidores' => implode(", ", array_unique($users['servidores'])),
                        'lotacoes' => implode(", ", array_unique($users['lotacoes'])),
                        'orgao' => implode(", ", array_unique($users['orgaos']))
                    ];
                }
            }
        }

        // echo '<pre>';
        // print_r($ips_listados);
        // echo '</pre>';
        // exit;

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => count($ips_listados),
            'iTotalDisplayRecords' => count($ips_listados),
            'aaData' => $ips_listados,
        ];

        return $response->withStatus(200)->withJson($resposta);
    }

    // Horas Trabalhada api_horas
    public function print_horas(Request $request, Response $response, $args)
    {
        $search = $request->getParams()['search'] ? $request->getParams()['search'] : false;
        $start = $request->getQueryParam('start') ? $request->getQueryParam('start') : 0;
        // $length = $request->getQueryParam('length') ? $request->getQueryParam('length') : 20;
        $order = isset(($request->getParams())['order']) ? ($request->getParams())['order'][0] : ['column' => 1, 'dir' => 'ASC'];

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;

        $pontos = [];
        $data = Date::create($request->getQueryParam('ano'), $request->getQueryParam('mes'));

        $column_name = [
            "matricula_usuario",
            "nome_usuario",
            "sigla_orgao",
            "descricao_lotacao",
            "total_hora",
        ];

        $data_inicial = $data->startOfMonth()->toDateString();
        $data_final = $data->endOfMonth()->toDateString();

        $mongoDB = new MongoDB();
        if ((Auth::perfil_usuario())['id_tipo_perfil'] <> 1) {
            $mongoDB->setFilter('id_orgao', intval(Auth::id_orgao_exercicio_usuario()));
        }


        if ($id_orgao) {
            $mongoDB->setFilter('id_orgao', intval($id_orgao));
        }

        if ($id_lotacao) {
            $mongoDB->setFilter('id_lotacao', intval($id_lotacao));
        }

        // $mongoDB->setFilter('finalidade_ponto', 'PONTO');
        $mongoDB->setFilter('$or', [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']]);
        // $mongoDB->setFilter('id_usuario', 32129);
        $mongoDB->setFilter('data_ponto', ['$gte' => $data_inicial . ' 00:00:00', '$lte' => $data_final . ' 23:59:59']);
        $mongoDB->setFilter('$or', [
            ['matricula_usuario' => new Regex($search)],
            ['nome_usuario' => new Regex($search)],
        ]);
        $pontos = $mongoDB->executeQuery();

        $servidores = [];
        $pontoServidores = [];
        $dadosRetorno = [];

        //ORGANIZA OS HORARIOS
        foreach ($pontos as $ponto) {
            if (!$servidores[$ponto->id_usuario . '-' . $ponto->id_lotacao]) {
                $servidores[$ponto->id_usuario . '-' . $ponto->id_lotacao] = $ponto;
            }

            $pontoServidores[$ponto->id_usuario . '-' . $ponto->id_lotacao][date('d', strtotime($ponto->data_ponto))][$ponto->tipo_ponto] = $ponto->data_ponto;
        }

        //CALCULA AS HORAS TRABALHADAS
        foreach ($servidores as $k => $servidor) {
            $dados = $servidor;
            $dados->total_hora = 0;
            $dias_trabalhados = 0;

            foreach ($pontoServidores[$k] as $kp => $ponto) {
                if ($ponto[1] && $ponto[2]) {
                    $e1  = new DateTime($ponto[1]);
                    $e2 = new DateTime($ponto[2]);
                    $intvl = $e1->diff($e2);
                    $dados->total_hora += ($intvl->h * 60) + $intvl->i;
                }
                if ($ponto[3] && $ponto[4]) {
                    $e3  = new DateTime($ponto[3]);
                    $e4 = new DateTime($ponto[4]);
                    $intvl = $e3->diff($e4);
                    $dados->total_hora += ($intvl->h * 60) + $intvl->i;
                }

                if (($ponto[1] && $ponto[2]) || ($ponto[3] && $ponto[4])) {
                    $dias_trabalhados++;
                }
            }

            $dados->total_hora = ($dias_trabalhados > 0 && $dados->total_hora > 0) ? intval($dados->total_hora / $dias_trabalhados) : 0;
            $dadosRetorno[] = (array) $dados;
        }

        //FAZ A ORDENAÇÃO
        $new_data = [];
        foreach ($dadosRetorno as $d) {
            $new_data[] = $d[$column_name[$order['column']]];
        }
        if (strtoupper($order['dir']) == 'ASC') {
            array_multisort($new_data, SORT_ASC, $dadosRetorno);
        } else {
            array_multisort($new_data,  SORT_DESC, $dadosRetorno);
        }



        return $this->view(
            $response,
            'relatorios',
            'show/horas/print',
            [
                'usuarios' => $dadosRetorno,
                'ano' => $request->getQueryParam('ano') != '' ? $request->getQueryParam('ano') : date('Y'),
                'mes' => $request->getQueryParam('mes') != '' ? $request->getQueryParam('mes') : date(),
                'qtd' => $request->getQueryParam('qtd') ?? 4,
            ]
        );
    }

    public function api_horas(Request $request, Response $response, $args)
    {
        $search = $request->getParams()['search'] ? $request->getParams()['search'] : false;
        $start = $request->getQueryParam('start') ? $request->getQueryParam('start') : 0;
        $length = $request->getQueryParam('length') ? $request->getQueryParam('length') : 20;
        $order = isset(($request->getParams())['order']) ? ($request->getParams())['order'][0] : ['column' => 1, 'dir' => 'ASC'];

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;

        $pontos = [];
        $data = Date::create($request->getQueryParam('ano'), $request->getQueryParam('mes'));

        $column_name = [
            "matricula_usuario",
            "nome_usuario",
            "sigla_orgao",
            "descricao_lotacao",
            "total_hora",
        ];

        $data_inicial = $data->startOfMonth()->toDateString();
        $data_final = $data->endOfMonth()->toDateString();

        $mongoDB = new MongoDB();
        if ((Auth::perfil_usuario())['id_tipo_perfil'] <> 1) {
            $mongoDB->setFilter('id_orgao', intval(Auth::id_orgao_exercicio_usuario()));
        }


        if ($id_orgao) {
            $mongoDB->setFilter('id_orgao', intval($id_orgao));
        }

        if ($id_lotacao) {
            $mongoDB->setFilter('id_lotacao', intval($id_lotacao));
        }

        $mongoDB->setFilter('$or', [['finalidade_ponto' => 'PONTO'], ['finalidade_ponto' => 'ABONO']]);
        $mongoDB->setFilter('data_ponto', ['$gte' => $data_inicial . ' 00:00:00', '$lte' => $data_final . ' 23:59:59']);
        $mongoDB->setFilter('$or', [
            ['matricula_usuario' => new Regex($search)],
            ['nome_usuario' => new Regex(strtoupper($search))],
        ]);
        $pontos = $mongoDB->executeQuery();

        $servidores = [];
        $pontoServidores = [];
        $dadosRetorno = [];

        //ORGANIZA OS HORARIOS
        foreach ($pontos as $ponto) {
            if (!$servidores[$ponto->id_usuario . '-' . $ponto->id_lotacao]) {
                $servidores[$ponto->id_usuario . '-' . $ponto->id_lotacao] = $ponto;
            }

            $pontoServidores[$ponto->id_usuario . '-' . $ponto->id_lotacao][date('d', strtotime($ponto->data_ponto))][$ponto->tipo_ponto] = $ponto->data_ponto;
        }

        //CALCULA AS HORAS TRABALHADAS
        foreach ($servidores as $k => $servidor) {
            $dados = $servidor;
            $dados->total_hora = 0;
            $dias_trabalhados = 0;

            foreach ($pontoServidores[$k] as $kp => $ponto) {
                if ($ponto[1] && $ponto[2]) {
                    $e1  = new DateTime($ponto[1]);
                    $e2 = new DateTime($ponto[2]);
                    $intvl = $e1->diff($e2);
                    $dados->total_hora += ($intvl->h * 60) + $intvl->i;
                }
                if ($ponto[3] && $ponto[4]) {
                    $e3  = new DateTime($ponto[3]);
                    $e4 = new DateTime($ponto[4]);
                    $intvl = $e3->diff($e4);
                    $dados->total_hora += ($intvl->h * 60) + $intvl->i;
                }

                if (($ponto[1] && $ponto[2]) || ($ponto[3] && $ponto[4])) {
                    $dias_trabalhados++;
                }
            }

            $dados->total_hora = ($dias_trabalhados > 0 && $dados->total_hora > 0) ? intval($dados->total_hora / $dias_trabalhados) : 0;
            $dadosRetorno[] = (array) $dados;
        }

        //FAZ A ORDENAÇÃO SE NECESSÁRIO
        $new_data = [];
        foreach ($dadosRetorno as $d) {
            $new_data[] = $d[$column_name[$order['column']]];
        }
        if (strtoupper($order['dir']) == 'ASC') {
            array_multisort($new_data, SORT_ASC, $dadosRetorno);
        } else {
            array_multisort($new_data,  SORT_DESC, $dadosRetorno);
        }

        //CRIA A PAGINAÇÃO SE NECESSÁRIO
        $servidores = [];
        if ($length == -1) {
            $servidores = $dadosRetorno;
        } else {
            $tt = 0;
            foreach ($dadosRetorno as $dados) {
                if ($tt >= $start && $tt < ($start + $length)) {
                    $servidores[] = $dados;
                }
                $tt++;
            }
        }

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => count($dadosRetorno),
            'iTotalDisplayRecords' => count($dadosRetorno),
            'aaData' => $servidores,
        ];

        return $response->withStatus(200)->withJson($resposta);
    }


    // Relatório: Escalas 

    public function print_escalas(Request $request, Response $response, $args)
    {
        $relatorio = Relatorio::where('link_relatorio', $args['link_relatorio'])->first();

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;
        $ja_finalizado = $request->getQueryParam('ja_finalizado') ? true : null;

        $ano = $request->getQueryParam('ano');
        $mes = $request->getQueryParam('mes');

        $usuarios = Escala::join('usuario', 'usuario.id_usuario', 'escala.id_usuario')
            ->join('orgao', 'orgao.id_orgao', 'usuario.id_orgao_exercicio_usuario')
            ->join('lotacao', 'lotacao.id_lotacao', 'usuario.id_lotacao_exercicio_usuario')
            ->with('DataEscala')
            ->where('usuario.situacao_usuario', 'A')
            ->where(function ($query) {
                if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2 || (Auth::perfil_usuario())['id_tipo_perfil'] == 4) {
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                } else if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
                    $query->whereIn('lotacao.id_lotacao',  function ($query) {
                        $query->select('id_lotacao')
                            ->from(with(new LotacaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                }
            })
            ->where(function ($query) use ($id_orgao) {
                if ($id_orgao) {
                    $query->where('orgao.id_orgao', $id_orgao);
                }
            })
            ->where(function ($query) use ($id_lotacao) {
                if ($id_lotacao) {
                    $query->where('lotacao.id_lotacao', $id_lotacao);
                }
            })
            ->whereIn('escala.id_escala', function ($query) use ($ano, $mes) {
                $query->select('data_escala.id_escala')
                    ->from(with(new DataEscala())->getTable())
                    ->whereRaw("data_escala.id_escala = escala.id_escala")
                    ->whereRaw("YEAR(data_escala.data_escala) = $ano")
                    ->whereRaw("MONTH(data_escala.data_escala) = $mes")
                    ->groupBy('data_escala.id_data_escala');
            })
            ->orderBy('orgao.sigla_orgao')
            ->orderBy('usuario.nome_usuario')
            ->get()->toArray();

        return $this->view(
            $response,
            'relatorios',
            'show/escalas/print',
            [
                'relatorio' => $relatorio,
                'usuarios' => $usuarios,
                'ano' => $request->getQueryParam('ano'),
                'mes' => $request->getQueryParam('mes'),
            ]
        );
    }

    public function api_escalas(Request $request, Response $response, $args)
    {
        $valid_lenght =  ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;

        $current_page = ceil((($request->getParams())['start'] + 1) / $valid_lenght);
        $length = ($request->getParams())['length'] ? ($request->getParams())['length'] : 10;

        $search = ($request->getParams())['search'] ? '%' . ($request->getParams())['search']  . '%' : false;

        $order = isset(($request->getParams())['order']) ? ($request->getParams())['order'][0] : null;

        $group_order = [
            0 => 'usuario.matricula_usuario',
            1 => 'usuario.contrato_usuario',
            2 => 'usuario.nome_usuario',
            4 => 'orgao.sigla_orgao',
            5 => 'lotacao.descricao_lotacao',
        ];

        if (!$order) {
            $order['column'] = 2;
            $order['dir'] = 'asc';
        }

        $id_orgao = $request->getQueryParam('id_orgao') ? $request->getQueryParam('id_orgao') : null;
        $id_lotacao = $request->getQueryParam('id_lotacao') ? $request->getQueryParam('id_lotacao') : null;
        $ja_finalizado = $request->getQueryParam('ja_finalizado') ? true : null;

        $ano = $request->getQueryParam('ano');
        $mes = $request->getQueryParam('mes');

        $pontos = [];

        $usuarios = Escala::join('usuario', 'usuario.id_usuario', 'escala.id_usuario')
            ->join('orgao', 'orgao.id_orgao', 'usuario.id_orgao_exercicio_usuario')
            ->join('lotacao', 'lotacao.id_lotacao', 'usuario.id_lotacao_exercicio_usuario')
            ->with('DataEscala')
            ->where('usuario.situacao_usuario', 'A')
            ->where(function ($query) {
                if ((Auth::perfil_usuario())['id_tipo_perfil'] == 2 || (Auth::perfil_usuario())['id_tipo_perfil'] == 4) {
                    $query->whereIn('orgao.id_orgao',  function ($query) {
                        $query->select('id_orgao')
                            ->from(with(new OrgaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                } else if ((Auth::perfil_usuario())['id_tipo_perfil'] == 3) {
                    $query->whereIn('lotacao.id_lotacao',  function ($query) {
                        $query->select('id_lotacao')
                            ->from(with(new LotacaoResponsavel())->getTable())
                            ->where('id_usuario', Auth::id_usuario());
                    });
                }
            })
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->where('escala.amparo_legal_escala', 'LIKE', $search)
                        ->orWhere('usuario.matricula_usuario', 'LIKE', $search)
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
            ->where(function ($query) use ($id_orgao) {
                if ($id_orgao) {
                    $query->where('orgao.id_orgao', $id_orgao);
                }
            })
            ->where(function ($query) use ($id_lotacao) {
                if ($id_lotacao) {
                    $query->where('lotacao.id_lotacao', $id_lotacao);
                }
            })
            ->whereIn('escala.id_escala', function ($query) use ($ano, $mes) {
                $query->select('data_escala.id_escala')
                    ->from(with(new DataEscala())->getTable())
                    ->whereRaw("data_escala.id_escala = escala.id_escala")
                    ->whereRaw("YEAR(data_escala.data_escala) = $ano")
                    ->whereRaw("MONTH(data_escala.data_escala) = $mes")
                    ->groupBy('data_escala.id_data_escala');
            })
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

    // Auditoria
    public function api_auditoria(Request $request, Response $response, $args)
    {

        $search = $request->getParams()['search'] ? $request->getParams()['search'] : false;

        $pontos = [];
        $data = Date::create($request->getParams()['ano'], $request->getParams()['mes']);

        $data_inicial = $data->startOfMonth()->toDateString();
        $data_final = $data->endOfMonth()->toDateString();

        //VERIFICA SE O SERVIDOR ESTÁ CADASTRADO NA BASE DE DADOS
        $servidor = Usuario::where(function ($query) use ($search) {
            //VERIFICA SE TEM CONTRATO
            $info = explode("-", $search);
            if (count($info) > 1) {
                return $query->where('matricula_usuario', $info[0])->where('contrato_usuario', $info[1]);
            }

            return $query->where('matricula_usuario', $search)->orWhere('cpf_usuario', $search);
        })->first();



        $mongoDB = new MongoDB();

        $mongoDB->setFilter('finalidade_ponto', 'PONTO');
        $mongoDB->setFilter('data_ponto', ['$gte' => $data_inicial . ' 00:00:00', '$lte' => $data_final . ' 23:59:59']);
        $mongoDB->setFilter('id_usuario', $servidor->id_usuario);
        $mongoDB->setOptions('sort', ['tipo_ponto' => -1]);
        $pontos = $mongoDB->executeQuery();

        $registros = [];

        foreach ($pontos as $ponto) {
            if ($registros[date('j', strtotime($ponto->data_ponto))][$ponto->tipo_ponto]) {
                $registros[date('j', strtotime($ponto->data_ponto))][$ponto->tipo_ponto] = [];
            }

            $mongoDB = new MongoDB();
            $mongoDB->setFilter('finalidade_ponto', 'PONTO');
            $mongoDB->setFilter('tipo_ponto', $ponto->tipo_ponto);
            $mongoDB->setFilter('ip_ponto', $ponto->ip_ponto);
            $mongoDB->setFilter('data_ponto', ['$gte' => date('Y-m-d', strtotime($ponto->data_ponto)) . ' 00:00:00', '$lte' => date('Y-m-d', strtotime($ponto->data_ponto)) . ' 23:59:59']);
            $mongoDB->setOptions('sort', ['tipo_ponto' => -1]);
            $batidas = $mongoDB->executeQuery();

            foreach ($batidas as $b) {
                if (!in_array($b->ip_ponto, ['10.1.9.102'])) {
                    $registros[date('j', strtotime($ponto->data_ponto))][$ponto->tipo_ponto][] = $b;
                }
            }
        }

        $resposta = [
            'draw' => (int) ($request->getParams())['draw'],
            'iTotalRecords' => count($pontos),
            'iTotalDisplayRecords' => count($pontos),
            'aaData' => $registros,
            'id_usuario' => $servidor->id_usuario,
        ];

        return $response->withStatus(200)->withJson($resposta);
    }
}
