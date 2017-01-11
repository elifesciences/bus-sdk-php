#!/usr/bin/env php
<?php

$log = fopen('/tmp/php-clean-output', 'w');
$tempName = tempnam(sys_get_temp_dir(), 'fixer-file');
$tempFile = fopen($tempName, 'w');
$inputFile = fopen('php://stdin', 'r');
$fixedFile = fopen('php://stdout', 'w');
fwrite($tempFile, stream_get_contents($inputFile));
fclose($tempFile);
exec("php-cs-fixer fix {$tempName}");
fwrite($fixedFile, file_get_contents($tempName));
