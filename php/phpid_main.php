<?php
require __DIR__ . '/init_log.php';
require $argv[2];
require __DIR__ . '/PHPID.php';
(new PHPID($argv[1], $argv[3], $argv[4]))->loop();
