<?php
namespace Flint\ControllerExtender\Provider;

use Flint\ControllerExtender\EasyPDO;
use Silex\ServiceProviderInterface;
use Silex\Application;

class PDOServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['pdo'] = $app->share(function () use ($app) {
            $driver = isset($app['db.driver']) ? $app['db.driver'] : 'mysql';
            $dsn    = $driver . ':host=' . $app['db.host'] . ';port=' . $app['db.port'] . ';dbname=' . $app['db.name'];
            $conn   = new EasyPDO($dsn,
                $app['db.user'],
                $app['db.password'],
                array(
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_STRINGIFY_FETCHES => false,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                )
            );
            $conn->exec('SET CHARACTER SET ' . (isset($app['db.charset']) ? $app['db.charset'] : 'UTF8'));
            //$conn->exec('SET NAMES ' . (isset($app['db.names']) ? $app['db.names'] : 'UTF8'));

            return $conn;
        });
    }

    public function boot(Application $app)
    {
    }
}
