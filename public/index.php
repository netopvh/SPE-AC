<?php

use App\Middlewares\RedirectMainRoute;
use Sabberworm\CSS\Property\Import;

ob_start();
session_start();
session_write_close();

error_reporting(1); //EXIBIR APENAS ERROS FATAL

require __DIR__ . '/../vendor/autoload.php';
// require __DIR__ . '/../vendor/dompdf/dompdf/autoload.inc.php';
// require __DIR__ . '/../vendor/snappy/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use App\Dependencies\BootLoader;
use App\Extensions\Support\Routing;
use App\Middlewares\SessionValidationMiddleware;

use App\Classes\ErrorRenderer;

use App\Models\Configuracao;
use App\Services\ImportacaoService;

// Create Container using PHP-DI
$container_temp = new Container();

//configura o container
$container = (new BootLoader($container_temp))->boot();

// Set container to create App with on AppFactory
AppFactory::setContainer($container);

// Instantiate App
$app = AppFactory::create();

// Inicializa conexão
$app->getContainer()->get('db');

$configuracoes = Configuracao::all()->toArray();

foreach ($configuracoes as $configuracao) {
    define(strtoupper($configuracao['chave_configuracao']), $configuracao['valor_configuracao']);
}

$service = new ImportacaoService();
$service->setDsn('folha')->importar();
