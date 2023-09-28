<?php

namespace App\Utils;


class ConectionMongoDB
{
    private static $instance;

    public static function getInstance() : \MongoDB\Driver\Manager
    {
        if (! isset(self::$instance)) {
            try {
                self::$instance = new \MongoDB\Driver\Manager('mongodb://' . DATABASE_MONGO['username'] . ':' . DATABASE_MONGO['password'] . '@' . DATABASE_MONGO['host'] . ':' . DATABASE_MONGO['port'] . '');
            } catch (\Throwable $th) {
                echo $th->getMessage();
            }
        }
        return self::$instance;
    }

    public static function connection($sql) : \MongoDB\Driver\Manager
    {
        return self::getInstance();
    }
}