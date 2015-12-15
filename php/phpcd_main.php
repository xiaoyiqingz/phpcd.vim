<?php
error_reporting(0);

require $argv[1] . '/vendor/autoload.php';
require __DIR__ . '/RpcServer.php';
require __DIR__ . '/PHPCD.php';

(new PHPCD)->loop();
