<?php

use App\Controllers\AbonoController;
use App\Controllers\AfastamentoController;
use App\Controllers\AutenticacaoController;
use App\Controllers\CalendarioController;
use App\Controllers\ColaboradorController;
use App\Controllers\ConfiguracaoController;
use App\Controllers\DashboardController;
use App\Controllers\DispensaController;
use App\Controllers\EscalaController;
use App\Controllers\FeriasController;
use App\Controllers\FolhaPontoController;
use App\Controllers\HorarioController;
use App\Controllers\ImportacaoController;
use App\Controllers\LotacaoController;
use App\Controllers\LotacaoResponsavelController;
use App\Controllers\OrganogramaController;
use App\Controllers\OrgaoController;
use App\Controllers\OrgaoResponsavelController;
use App\Controllers\PerfilUsuarioController;
use App\Controllers\PermissaoController;
use App\Controllers\PontoController;
use App\Controllers\RegistroController;
use App\Controllers\RelatorioController;
use App\Controllers\ServidorController;
use Slim\Routing\RouteCollectorProxy;

use App\Middlewares\RedirectAuthenticated;
use App\Middlewares\RedirectNotAuthenticated;
// use Tuupola\Middleware\CorsMiddleware;

use App\Utils\Auth;

// $app->add(new CorsMiddleware([
//     "origin" => ["*"],
//     "methods" => ["GET", "POST", "PATCH", "DELETE", "OPTIONS"],    
//     "headers.allow" => ["Origin", "Content-Type", "Authorization", "Accept", "ignoreLoadingBar", "X-Requested-With", "Access-Control-Allow-Origin"],
//     "headers.expose" => [],
//     "credentials" => true,
//     "cache" => 0,        
// ]));

$app->get('/', [PontoController::class, 'index']);

$app->post('/pontos', [PontoController::class, 'store']);
$app->any('/api/pontos', [PontoController::class, 'api_index']);

$app->group('/autenticacao', function (RouteCollectorProxy $group) {
    $group->any('/login', [AutenticacaoController::class, 'login'])->add(new RedirectAuthenticated());
    $group->any('/login/alterar', [AutenticacaoController::class, 'alterar'])->add(new RedirectNotAuthenticated());
    $group->any('/logout', [AutenticacaoController::class, 'logout'])->add(new RedirectNotAuthenticated());
    $group->any('/forgot', [AutenticacaoController::class, 'forgot'])->add(new RedirectAuthenticated());
});

$app->any('/dashboard', [DashboardController::class, 'index'])->add(new RedirectNotAuthenticated());

$app->group('/api', function (RouteCollectorProxy $group) {

    $group->group('/abonos', function (RouteCollectorProxy $group) {
        $group->any('', [AbonoController::class, 'api_index']);
        $group->any('/retornados', [AbonoController::class, 'pontos_retornados']);
    });

    $group->group('/abonos_servidor', function (RouteCollectorProxy $group) {
        $group->any('/devolvidos', [AbonoController::class, 'pontos_devolvidos']);
    });

    if (Auth::logged() and Auth::perfil_usuario()) {
        $group->group('/servidores', function (RouteCollectorProxy $group) {
            $group->any('', [ServidorController::class, 'api_index']);
        });

        $group->group('/perfis_usuario', function (RouteCollectorProxy $group) {
            $group->any('', [PerfilUsuarioController::class, 'api_index']);
        });

        if ((Auth::perfil_usuario())['id_tipo_perfil'] != 3) {
            $group->group('/colaboradores', function (RouteCollectorProxy $group) {
                $group->any('', [ColaboradorController::class, 'api_index']);
            });
        }

        if ((Auth::perfil_usuario())['id_tipo_perfil'] != 3) {
            $group->group('/dispensas', function (RouteCollectorProxy $group) {
                $group->any('', [DispensaController::class, 'api_index']);
            });
        }

        if ((Auth::perfil_usuario())['id_tipo_perfil'] != 3) {
            $group->group('/escalas', function (RouteCollectorProxy $group) {
                $group->any('', [EscalaController::class, 'api_index']);
            });
        }

        $group->group('/orgaos', function (RouteCollectorProxy $group) {
            $group->any('', [OrgaoController::class, 'api_index']);
        });

        $group->group('/orgaos_responsaveis', function (RouteCollectorProxy $group) {
            $group->any('', [OrgaoResponsavelController::class, 'api_index']);
        });

        $group->group('/folhas_ponto', function (RouteCollectorProxy $group) {
            $group->any('', [FolhaPontoController::class, 'api_index']);

            if ((Auth::perfil_usuario())['id_tipo_perfil'] != 4) {
                $group->any('/count', [FolhaPontoController::class, 'count']);
            }
        });

        $group->group('/importacao', function (RouteCollectorProxy $group) {
            $group->any('', [ImportacaoController::class, 'api_index']);
        });

        $group->group('/lotacoes', function (RouteCollectorProxy $group) {
            $group->any('', [LotacaoController::class, 'api_index']);
        });

        $group->group('/lotacoes_responsaveis', function (RouteCollectorProxy $group) {
            $group->any('', [LotacaoResponsavelController::class, 'api_index']);
            $group->any('/afastamento-temporario/{id}', [LotacaoResponsavelController::class, 'afastamento_temporario']);
            $group->any('/historico', [LotacaoResponsavelController::class, 'historicoChefia']);
        });

        $group->group('/calendarios', function (RouteCollectorProxy $group) {
            $group->any('', [CalendarioController::class, 'api_index']);
        });

        $group->group('/relatorios', function (RouteCollectorProxy $group) {
            $group->any('', [RelatorioController::class, 'api_index']);
            $group->any('/{link_relatorio}', [RelatorioController::class, 'api_relatorio']);
        });

        $group->group('/horarios', function (RouteCollectorProxy $group) {
            $group->any('', [HorarioController::class, 'api_index']);
        });

        $group->group('/permissoes', function (RouteCollectorProxy $group) {
            $group->any('', [PermissaoController::class, 'api_index']);
        });

        $group->group('/ferias', function (RouteCollectorProxy $group) {
            $group->any('', [FeriasController::class, 'api_index']);
        });

        $group->group('/afastamentos', function (RouteCollectorProxy $group) {
            $group->any('', [AfastamentoController::class, 'api_index']);
        });

        if ((Auth::perfil_usuario())['id_tipo_perfil'] == 1) {
            $group->group('/configuracoes', function (RouteCollectorProxy $group) {
                $group->any('', [ConfiguracaoController::class, 'api_index']);
            });
        }
    }
})->add(new RedirectNotAuthenticated());

$app->group('/registros', function (RouteCollectorProxy $group) {
    $group->any('', [RegistroController::class, 'index']);
    $group->any('/imprimir', [RegistroController::class, 'print']);
})->add(new RedirectNotAuthenticated());

$app->any('/abonos/visualizar/{id}', [AbonoController::class, 'show'])->add(new RedirectNotAuthenticated());

$app->group('/abonos_servidor', function (RouteCollectorProxy $group) {
    $group->any('', [AbonoController::class, 'index_servidor']);
    $group->any('/cadastrar', [AbonoController::class, 'store']);
    $group->any('/editar/{id}', [AbonoController::class, 'update']);
    $group->delete('/deletar/{id}', [AbonoController::class, 'destroy']);
})->add(new RedirectNotAuthenticated());

$app->group('/organograma', function (RouteCollectorProxy $group) {
    $group->any('', [OrganogramaController::class, 'index']);
    $group->any('/exibir', [OrganogramaController::class, 'exibir']);
    $group->any('/imprimir', [OrganogramaController::class, 'print']);
})->add(new RedirectNotAuthenticated());

if (Auth::logged() and Auth::perfil_usuario()) {

    $app->group('/servidores', function (RouteCollectorProxy $group) {
        $group->any('', [ServidorController::class, 'index']);
        $group->any('/visualizar/{id}', [ServidorController::class, 'show']);

        if ((Auth::perfil_usuario())['id_tipo_perfil'] != 4) {
            $group->any('/editar/{id}', [ServidorController::class, 'update']);
        }
    })->add(new RedirectNotAuthenticated());

    if ((Auth::perfil_usuario())['id_tipo_perfil'] != 3) {
        $app->group('/colaboradores', function (RouteCollectorProxy $group) {
            $group->any('', [ColaboradorController::class, 'index']);
            $group->any('/visualizar/{id}', [ColaboradorController::class, 'show']);
            if ((Auth::perfil_usuario())['id_tipo_perfil'] != 4) {
                $group->any('/cadastrar', [ColaboradorController::class, 'store']);
                $group->any('/editar/{id}', [ColaboradorController::class, 'update']);
            }
        })->add(new RedirectNotAuthenticated());
    }

    if ((Auth::perfil_usuario())['id_tipo_perfil'] != 3) {
        $app->group('/orgaos', function (RouteCollectorProxy $group) {
            $group->any('', [OrgaoController::class, 'index']);
            $group->any('/visualizar/{id}', [OrgaoController::class, 'show']);
            $group->any('/update', [OrgaoController::class, 'update']);

            if ((Auth::perfil_usuario())['id_tipo_perfil'] != 4) {
                $group->any('/{id}/responsaveis', [OrgaoResponsavelController::class, 'index']);
                $group->any('/{id}/responsaveis/deletar/{id_orgao_responsavel}', [OrgaoResponsavelController::class, 'delete']);
            }
        })->add(new RedirectNotAuthenticated());
    }

    if ((Auth::perfil_usuario())['id_tipo_perfil'] != 3) {
        $app->group('/lotacoes', function (RouteCollectorProxy $group) {
            $group->any('', [LotacaoController::class, 'index']);
            $group->any('/visualizar/{id}', [LotacaoController::class, 'show']);

            if ((Auth::perfil_usuario())['id_tipo_perfil'] != 4) {
                $group->any('/{id}/responsaveis', [LotacaoResponsavelController::class, 'index']);
                $group->any('/{id}/responsaveis/deletar/{id_lotacao_responsavel}', [LotacaoResponsavelController::class, 'delete']);
            }

            $group->any('/remover/{id}', [LotacaoController::class, 'remover']);
        })->add(new RedirectNotAuthenticated());
    }

    if ((Auth::perfil_usuario())['id_tipo_perfil'] != 4) {
        $app->group('/verificar_token', function (RouteCollectorProxy $group) {
            $group->any('', [FolhaPontoController::class, 'verify']);
        })->add(new RedirectNotAuthenticated());
    }

    $app->group('/abonos', function (RouteCollectorProxy $group) {
        $group->any('', [AbonoController::class, 'index']);

        if ((Auth::perfil_usuario())['id_tipo_perfil'] != 4) {
            $group->any('/servidor/{id}', [AbonoController::class, 'servidor']);
            $group->any('/{id}/deferir', [AbonoController::class, 'deferir']);
            $group->any('/{id}/devolver', [AbonoController::class, 'devolver']);
            $group->any('/{id}/indeferir', [AbonoController::class, 'indeferir']);
        }
    })->add(new RedirectNotAuthenticated());

    if ((Auth::perfil_usuario())['id_tipo_perfil'] != 3) {
        $app->group('/calendarios', function (RouteCollectorProxy $group) {
            $group->any('', [CalendarioController::class, 'index']);
            $group->any('/visualizar/{id}', [CalendarioController::class, 'show']);

            if ((Auth::perfil_usuario())['id_tipo_perfil'] == 1) {
                $group->any('/cadastrar', [CalendarioController::class, 'store']);
                $group->any('/editar/{id}', [CalendarioController::class, 'update']);
                $group->delete('/deletar/{id}', [CalendarioController::class, 'destroy']);
            }

        })->add(new RedirectNotAuthenticated());
    }

    if ((Auth::perfil_usuario())['id_tipo_perfil'] != 3) {
        $app->group('/relatorios', function (RouteCollectorProxy $group) {
            $group->any('', [RelatorioController::class, 'index']);
            $group->any('/visualizar/{link_relatorio}', [RelatorioController::class, 'show']);
            $group->any('/imprimir/{link_relatorio}', [RelatorioController::class, 'print']);
        })->add(new RedirectNotAuthenticated());
    }

    if ((Auth::perfil_usuario())['id_tipo_perfil'] != 3) {
        $app->group('/dispensas', function (RouteCollectorProxy $group) {
            $group->any('', [DispensaController::class, 'index']);

            if ((Auth::perfil_usuario())['id_tipo_perfil'] != 4) {
                $group->post('/cadastrar', [DispensaController::class, 'store']);
                $group->post('/editar/{id}', [DispensaController::class, 'update']);
                $group->delete('/deletar/{id}', [DispensaController::class, 'delete']);
            }

        })->add(new RedirectNotAuthenticated());
    }

    if ((Auth::perfil_usuario())['id_tipo_perfil'] != 3) {
        $app->group('/escalas', function (RouteCollectorProxy $group) {
            $group->any('', [EscalaController::class, 'index']);

            if ((Auth::perfil_usuario())['id_tipo_perfil'] != 4) {
                $group->any('/cadastrar', [EscalaController::class, 'store']);
                $group->any('/editar/{id}', [EscalaController::class, 'update']);
                $group->delete('/deletar/{id}', [EscalaController::class, 'delete']);
            }

        })->add(new RedirectNotAuthenticated());
    }

    if ((Auth::perfil_usuario())['id_tipo_perfil'] != 3) {
        $app->group('/horarios', function (RouteCollectorProxy $group) {
            $group->any('', [HorarioController::class, 'index']);
            $group->any('/visualizar/{id}', [HorarioController::class, 'show']);
            $group->any('/padrao/{id}', [HorarioController::class, 'padrao']);


            $group->any('/corrigir', [HorarioController::class, 'corrigir']);

            if ((Auth::perfil_usuario())['id_tipo_perfil'] == 1) {
                $group->any('/cadastrar', [HorarioController::class, 'store']);
                $group->any('/editar/{id}', [HorarioController::class, 'update']);
                $group->delete('/deletar/{id}', [HorarioController::class, 'delete']);
            }

        })->add(new RedirectNotAuthenticated());
    }

    if ((Auth::perfil_usuario())['id_tipo_perfil'] != 3 && (Auth::perfil_usuario())['id_tipo_perfil'] != 4) {
        $app->group('/permissoes', function (RouteCollectorProxy $group) {
            $group->any('', [PermissaoController::class, 'index']);
            $group->any('/cadastrar', [PermissaoController::class, 'store']);
            $group->any('/deletar/{id}', [PermissaoController::class, 'delete']);
        })->add(new RedirectNotAuthenticated());
    }

    $app->group('/ferias', function (RouteCollectorProxy $group) {
        $group->any('', [FeriasController::class, 'index']);
    })->add(new RedirectNotAuthenticated());

    $app->group('/afastamentos', function (RouteCollectorProxy $group) {
        $group->any('', [AfastamentoController::class, 'index']);
    })->add(new RedirectNotAuthenticated());

    $app->group('/folhas_ponto', function (RouteCollectorProxy $group) {
        $group->any('', [FolhaPontoController::class, 'index']);
        $group->any('/visualizar', [FolhaPontoController::class, 'show']);
        $group->any('/imprimir', [FolhaPontoController::class, 'print']);
    })->add(new RedirectNotAuthenticated());

    if ((Auth::perfil_usuario())['id_tipo_perfil'] == 1) {
        $app->group('/configuracoes', function (RouteCollectorProxy $group) {
            $group->any('', [ConfiguracaoController::class, 'index']);
            $group->post('/cadastrar', [ConfiguracaoController::class, 'store']);
            $group->post('/editar/{id}', [ConfiguracaoController::class, 'update']);
        })->add(new RedirectNotAuthenticated());

        $app->group('/importacao', function (RouteCollectorProxy $group) {
            $group->any('', [ImportacaoController::class, 'index']);
            $group->any('/verificar', [ImportacaoController::class, 'verificar']);
        })->add(new RedirectNotAuthenticated());
    }
}