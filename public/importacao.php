<?php

error_reporting(1); //EXIBIR APENAS ERROS FATAL

require __DIR__ . '/../vendor/autoload.php';
// require __DIR__ . '/../vendor/dompdf/dompdf/autoload.inc.php';
// require __DIR__ . '/../vendor/snappy/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use App\Dependencies\BootLoader;

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

try {
	$service = new \App\Services\ImportacaoService();
	$service->setDsn(ODBC_CON)->importar();

	echo "Importação realizada com sucesso!";
} catch (\Exception $e) {
	echo $e->getMessage();
}
