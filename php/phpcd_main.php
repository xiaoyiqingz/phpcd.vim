<?php
fclose(STDIN);
fclose(STDOUT);
fclose(STDERR);
$home_path = getenv('HOME');
$STDIN = fopen('/dev/null', 'r');
$STDOUT = fopen($home_path . '/.phpcd.log', 'a');
$STDERR = $STDOUT;

require __DIR__ . '/PHPCD.php';
(new PHPCD($argv[1], $argv[2]))->loop();
