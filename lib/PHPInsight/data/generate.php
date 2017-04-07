#!/usr/bin/php
<?php

$sourcePath = __DIR__ . '/../dictionaries/';

$types = array(
    'ign',
    'neg',
    'neu',
    'pos',
    'prefix',
    'split',
); 

//Try read source dir
if (!$sourceDirectory = scandir($sourcePath)) {
    echo "Error : Can't read dir " . $sourcePath . ".\n";
    exit(1);
}

//Foreach dir (each dir is a language), searching for sources files
echo "Start generate...\n\n";
foreach ($sourceDirectory as $directory) {
    if ($directory == '.' || $directory == '..') {
        continue;
    }

    echo '    Processing lang : ' . $directory . " ...\n";

    //Foreach type of source, generate & write data file
    foreach ($types as $type) {
        echo '        Processing for type : ' . $type . " ...\n";

        require $sourcePath . '/' . $directory . '/source.' . $type . '.php';

        $serializeSource = serialize($$type);

        if (!file_put_contents(__DIR__ . '/' . $directory . '/data.' . $type . '.php', $serializeSource)) {
            echo '    Error : Can\'t write file ' . __DIR__ . '/' . $directory . '/data.' . $type . '.php' . "\n";
        }

    }

    echo "    ...Done.\n\n";
}

echo "...End.\n";
