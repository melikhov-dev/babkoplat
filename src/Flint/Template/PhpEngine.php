<?php
namespace Flint\Template;

use Silex\Application;

class PhpEngine implements TemplateEngineInterface
{
    protected $directories;
    protected $ext = 'php';
    protected $debug = false;

    protected $cacheData;

    public function __construct(array $directories, $debug = false)
    {
        $this->directories = $directories;
        $this->debug = $debug;
    }

    public function render($template, array $data = array())
    {
        ob_start();
        try {
            foreach ($this->directories as $dir) {
                $file = $dir . '/' . $template . '.' . $this->ext;
                if (file_exists($file)) {
                    ob_start();
                    extract($data);
                    include $file;
                    return ob_get_clean();
                }

            }
        } catch (\Exception $e) {
            ob_clean();
            throw $e;
            ob_start();
        }
        return ob_get_clean();
    }


    public function cache()
    {

    }
}
