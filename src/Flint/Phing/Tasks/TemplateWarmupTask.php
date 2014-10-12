<?php

class TemplateWarmupTask extends \Flint\Phing\AbstractTask
{
    /**
     * The main entry point method.
     */
    public function execute(\Silex\Application $app)
    {

        $app['template']->setTemplateFile('templates.php.cache');
        $app['template']->cache();
        $this->write('Template cache warmed');
    }
}
