<?php

namespace App\Utils;

class ConectionTurmalina {
    private static $instance;

    public static function getInstance() {
        if (!isset(self::$instance)) {
            try {
                self::$instance = oci_connect( DATABASE_TURMALINA['username'],DATABASE_TURMALINA['password'], '(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = ' . DATABASE_TURMALINA['host'] . ' )(PORT = ' . DATABASE_TURMALINA['port'] . ' ))(CONNECT_DATA = (SERVER = DEDICATED)(SERVICE_NAME = ' . DATABASE_TURMALINA['service'] . ' )))', 'AL32UTF8');
            } catch (\Throwable $th) {
                echo $th->getMessage();
            }
        }
        return self::$instance;
    }

    public static function connection($sql) {
        return self::getInstance();
    }
}
