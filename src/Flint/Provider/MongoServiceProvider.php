<?php
namespace Flint\Provider;

use Silex\ServiceProviderInterface;
use Silex\Application;

class MongoServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['mongo'] = $app->share(function ($app) {
            $connection = new \MongoClient('mongodb://' . $app['mongo.host'] ,
            [
                'connectTimeoutMS' => 30000,
                //'wTimeoutMS' => 1200,
                'connect' => 1
            ]);
            return $connection->{$app['mongo.name']};
        });
    }

    public function boot(Application $app) {}
}

