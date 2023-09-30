<?php

require __DIR__ . '/../vendor/autoload.php';

use GO\Scheduler;

$scheduler = new Scheduler();

$scheduler->php(dirname(__DIR__, 1).DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'scheduler.php')
    ->everyMinute();

$scheduler->run();
