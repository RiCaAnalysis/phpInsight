<?php

require_once __DIR__ . './../vendor/autoload.php';

echo "Generate dictionaries\n";
(new PHPInsight\Sentiment())->reloadDictionaries();
echo "Done\n";
