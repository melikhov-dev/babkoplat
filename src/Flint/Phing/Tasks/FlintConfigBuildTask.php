<?php
require_once(__DIR__ . '/ConfigGenerator.php');

class FlintConfigBuildTask extends \Task
{
    private $tags;
    private $dest;
    private $source;
    private $vars;

    public function setTags($tags)
    {
        $this->tags = explode(',', str_replace([' ', "\n", "\r", "\t"], '', $tags));
    }

    public function setVars($vars)
    {
        $this->vars = explode(',', str_replace([' ', "\n", "\r", "\t"], '', $vars));
    }

    public function setDest($dest)
    {
        $this->dest = $dest;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function init()
    {
    }

    public function main()
    {
        $vars = [];
        foreach ($this->vars as $var) {
            $vars['%var.' . $var . '%'] = '\' . $' . $var . ' . \'';
        }

        $generator = new ConfigGenerator($this->tags, $vars);

        $list = $generator->generatePhpFile($this->source, $this->dest);
        echo implode(PHP_EOL, $list);
    }
}
