<?php
error_reporting(0);

require $argv[1] . '/vendor/autoload.php';
require __DIR__ . '/RpcServer.php';
require __DIR__ . '/PHPCD.php';

do {
    $pid = pcntl_fork();
    if ($pid > 0) {
        pcntl_waitpid($pid, $status);
    } elseif ($pid === 0) {
        (new PHPCD)->loop();
        exit;
    }
} while ($pid !== -1);
