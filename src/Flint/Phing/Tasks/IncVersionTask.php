<?php

class IncVersionTask extends \Flint\Phing\AbstractTask
{
    protected $file = null;

    public function setFile($file) {
        $this->file = $file;
    }

    public function execute(\Silex\Application $app)
    {
        $versioinFilePath = APPPATH.'../'.$this->file;

        file_put_contents($versioinFilePath,'{"version":'.date('YmdHi').'}');


    }
}
