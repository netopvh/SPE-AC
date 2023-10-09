<?php

define('ambiente', 'develop'); // local, develop, homologation, production

//AMBIENTE NETO
define('APP_DATABASE', [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'dados',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => ''
]);

//AMBIENTE SERVIDOR
//define('APP_DATABASE', [
//    'driver' => 'mysql',
//    'host' => 'localhost',
//    'port' => '3306',
//    'database' => 'ponto',
//    'username' => 'root',
//    'password' => '',
//    'charset' => 'utf8',
//    'collation' => 'utf8_unicode_ci',
//    'prefix' => ''
//]);

define('DATABASE_MONGO', [
    'host' => 'localhost',
    'port' => '27017',
    'database' => 'dados',
    'username' => 'admin',
    'password' => 'example',
]);

//define('DATABASE_MONGO', [
//    'host' => 'localhost',
//    'port' => '27017',
//    'database' => 'ponto',
//    'username' => '',
//    'password' => '',
//]);


define('DATABASE_SEICT', [

    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'database',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => ''
]);


define('DATABASE_TURMALINA', [
    'host' => 'localhost',
    'port' => '1521',
    'database' => 'database',
    'username' => 'root',
    'password' => '',
    'service' => 'semti',
    'mode' => 'WE8ISO8859P1'
]);


define('CONNECTION_LDAP', [
    'host' => 'localhost',
    'port' => '1389',
    'DN' => 'o=usuarios,DC=AC,DC=GOV,C=BR',
]);
