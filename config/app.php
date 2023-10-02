<?php

define('APP_NAME', 'Sistema de Ponto Eletrônico');

define('APP_SIGLA_NAME', 'SPE');

define('BASE_PATH', '/spe_novo');

define('TURMALINA', false); // Busca dados da Turmalina

define('BASE_URL', '//' . $_SERVER['HTTP_HOST']);

define('APP_URL', '//' . BASE_URL . BASE_PATH);

define('STORAGE_DIR', dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR);

// define('APP_DIR', (PHP_OS == 'Linux') ? '/var/www/html/spe_novo/' : 'C:\\wamp64\\www\\spe_novo\\');
define('APP_DIR', $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/');

define('APP_LANG', 'pt-BR');

define('APP_VERSION', '0001');

define('DATA_INSERCAO_ATUALIZACAO', Date('Y-m-d H:i:s'));

define('ODBC_CON', 'folha');
