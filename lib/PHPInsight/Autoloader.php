<?php

namespace PHPInsight;

class Autoloader
{
    /**
     * @var string
     */
    private $directory;
    private string $prefix;
    private int $prefixLength;

    /**
     * @param string $baseDirectory Base directory where the source files are located.
     */
    public function __construct($baseDirectory = __DIR__)
    {
        $this->directory = $baseDirectory;
        $this->prefix = __NAMESPACE__ . '\\';
        $this->prefixLength = strlen($this->prefix);
    }
}
