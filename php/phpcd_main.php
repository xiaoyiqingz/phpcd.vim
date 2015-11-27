<?php
require $argv[2];
require __DIR__ . '/PHPCD.php';
(new PHPCD($argv[1]))->loop();
