<?php
error_reporting(0);

require $argv[1] . '/vendor/autoload.php';

require __DIR__ . '/RpcServer.php';
require __DIR__ . '/PHPCD.php';
require __DIR__ . '/Reflection/ReflectionClass.php';

(new PHPCD($argv[1]))->loop();
