<?php
error_reporting(0);

$root = $argv[1];

require $root . '/vendor/autoload.php';
require __DIR__ . '/RpcServer.php';
require __DIR__ . '/PHPID.php';

do {
    $pid = pcntl_fork();
    if ($pid > 0) {
        pcntl_waitpid($pid, $status);
    } elseif ($pid === 0) {
        (new PHPID)->setRoot($root)->loop();
        exit;
    }
} while ($pid !== -1);
