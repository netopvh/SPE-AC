<?php

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
use App\Middlewares\SessionValidationMiddleware;

use App\Classes\ErrorRenderer;

use App\Models\Configuracao;

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

// BaseUrl Location
$app->setBasePath('/spe_novo');

// Add error middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->registerErrorRenderer('text/html', ErrorRenderer::class);


$app->add(new SessionValidationMiddleware());

// Add routes
require_once '../routes/router.php';
// foreach (Routing::autoload() as $file) {
//     require_once $file;
// }

$app->run();
ob_end_flush();
