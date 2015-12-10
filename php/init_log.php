<?php
$log_path = getenv('HOME') . '/.phpcd.log';
$__ob_file = fopen($log_path, 'a');

ob_start(function ($buffer) use($__ob_file) {
    fwrite($__ob_file, $buffer);
});

