<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Antoxa
 * Date: 29.05.13
 * Time: 14:39
 */


namespace Flint\Provider;

use Silex\ServiceProviderInterface;
use Silex\Application;

class SphinxServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['sphinx'] = $app->share(
            function ($app) {
                $sphinx = new \SphinxClient();
                $sphinx->SetServer($app['sphinx.config.server'], $app['sphinx.config.port']);
                $sphinx->SetMatchMode(SPH_MATCH_ANY);

                $sphinx->SetSortMode(SPH_SORT_RELEVANCE,'end_date');
                $sphinx->SetLimits(0,500);

                return $sphinx;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}


