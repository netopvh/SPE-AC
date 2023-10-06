<?php

namespace App\Utils;


class ConectionMongoDB
{
    private static $instance;

    public static function getInstance(): \MongoDB\Driver\Manager
    {
        if (!isset(self::$instance)) {
            try {
                if (DATABASE_MONGO['username'] !== '' && DATABASE_MONGO['password'] !== '') {
                    $username = DATABASE_MONGO['username'];
                    $password = DATABASE_MONGO['password'];
                    $auth = $username . ':' . $password . '@';
                } else {
                    $auth = '';
                }

                $dsn = 'mongodb://' . $auth . DATABASE_MONGO['host'] . ':' . DATABASE_MONGO['port'];

                self::$instance = new \MongoDB\Driver\Manager($dsn);
            } catch (\Throwable $th) {
                echo $th->getMessage();
            }
        }
        return self::$instance;
    }

    public static function connection($sql): \MongoDB\Driver\Manager
    {
        return self::getInstance();
    }
}
