<?php
namespace Flint\Provider;

use Flint\Template\JadeEngine;
use Flint\Template\PhpEngine;
use Silex\Application;
use Silex\ServiceProviderInterface;

class TemplateServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['template'] = $app->share(function ($app) {
            return new JadeEngine($app['template.cache.path'], $app['template.directories'], true, $app['debug']);
        });

        $app['template.php'] = $app->share(function ($app) {
            return new PhpEngine( $app['template.directories'], $app['debug']);
        });
    }

    public function boot(Application $app) {}
}
