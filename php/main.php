<?php
error_reporting(0);

$root   = $argv[1];
$daemon = $argv[2];

require $root . '/vendor/autoload.php';
require __DIR__ . '/RpcServer.php';

try {
    switch ($daemon) {
        case 'PHPCD':
            require __DIR__ . '/PHPCD.php';
            require __DIR__ . '/Reflection/ReflectionClass.php';
            break;
        case 'PHPID':
            require __DIR__ . '/PHPID.php';
            break;
        default:
            throw new \InvalidArgumentException('The second parameter should be PHPCD or PHPID');
    }

    $unpacker = new \MessagePackUnpacker;

    // @TODO create logger instance,
    // inject it as an external dependency of RpcServer
    // and replace error_log
    (new $daemon($root, $unpacker))->loop();
} catch (\Throwable $e) {
    error_log($e->getMessage());
} catch (\Exception $e) {
    error_log($e->getMessage());
}
