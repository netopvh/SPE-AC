<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Services\ImportacaoService;

$service = new ImportacaoService();
$service->setDsn('folha')->importar();

