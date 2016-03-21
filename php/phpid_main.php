<?php
error_reporting(0);

$root = $argv[1];

require $root . '/vendor/autoload.php';
require __DIR__ . '/RpcServer.php';
require __DIR__ . '/PHPID.php';

(new PHPID($root))->loop();
