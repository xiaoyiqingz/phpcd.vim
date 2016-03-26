<?php
error_reporting(0);

$root   = $argv[1];
$daemon = $argv[2];

/** load autoloader for PHPCD **/
require __DIR__ . '/../vendor/autoload.php';
/** load autoloader for the project **/
require $root . '/vendor/autoload.php';

$log_path = getenv('HOME') . '/.phpcd.log';
$logger = new PHPCD\Logger($log_path);

try {
    switch ($daemon) {
        case 'PHPCD':
        case 'PHPID':
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
