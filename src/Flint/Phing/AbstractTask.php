<?php
namespace Flint\Phing;

use Silex\Application;

abstract class AbstractTask extends \Task
{
    /**
     * The message passed in the buildfile.
     */
    protected $env = 'prod';

    /** @var $app \Silex\Application */
    static protected $app;

    protected $bootstrap;

    /**
     * The setter for the attribute "message"
     */
    public function setEnv($str)
    {
        $this->env = $str;
    }

    /**
     * Application bootstrap script path
     */
    public function setBootstrap($bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * The init method: Do init steps.
     */
    public function init()
    {
    }

    public function main()
    {
        if (!self::$app) {
            $bootstrapper = include_once $this->bootstrap;
            self::$app = $bootstrapper();
        }
        $this->execute(self::$app);
    }

    abstract function execute(Application $app);

    /*
     * Show console message
     *
     * @param string $message
     * @param mixed arguments
     */
    public function write()
    {
        $args = func_get_args();
        $s = $args[0];
        unset($args[0]);

        echo vsprintf($s . PHP_EOL, $args);
    }
}
