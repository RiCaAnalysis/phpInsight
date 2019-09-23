<?php

require_once "PHPInsight/Sentiment.php";
require_once "PHPInsight/Autoloader.php";

echo "Generate dictionaries\n";
(new PHPInsight\Sentiment())->reloadDictionaries();
echo "Done\n";
