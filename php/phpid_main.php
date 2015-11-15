<?php
fclose(STDIN);
fclose(STDOUT);
fclose(STDERR);
$home_path = getenv('HOME');
$STDIN = fopen('/dev/null', 'r');
$STDOUT = fopen($home_path . '/.phpcd.log', 'a');
$STDERR = $STDOUT;

require __DIR__ . '/PHPID.php';
(new PHPID($argv[1], $argv[2], $argv[3], $argv[4]))->loop();
