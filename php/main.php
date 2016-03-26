<?php
error_reporting(0);

$root   = $argv[1];
$daemon = $argv[2];


/** It would to avoid manual require each used class. **/
require __DIR__ . '/../vendor/autoload.php';
require $root . '/vendor/autoload.php';
// require __DIR__ . '/RpcServer.php';

$log_path = getenv('HOME') . '/.phpcd.log';
$logger = new PHPCD\Logger($log_path);

try {
    switch ($daemon) {
        case 'PHPCD':
            // require __DIR__ . '/PHPCD.php';
            // require __DIR__ . '/Reflection/ReflectionClass.php';
            break;
        case 'PHPID':
            // require __DIR__ . '/PHPID.php';
            break;
        default:
            throw new \InvalidArgumentException('The second parameter should be PHPCD or PHPID');
    }

    $daemon = '\\PHPCD\\'.$daemon;
    $unpacker = new \MessagePackUnpacker;

    (new $daemon($root, $unpacker, $logger))->loop();
} catch (\Throwable $e) {
    $logger->emergency($e->getMessage(), $e->getTrace());
} catch (\Exception $e) {
    $logger->emergency($e->getMessage(), $e->getTrace());
}
