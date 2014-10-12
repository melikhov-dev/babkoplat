<?php
namespace Flint\Validation\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

use Flint\Validation\Service\Validator;

class ValidatorProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        \Valitron\Validator::ruleMessages($app['validator.messages']);
        $app['flint.validator'] = $app->share(function() use ($app) {
            return new Validator($app['available_validators']);
        });
    }

    public function boot(Application $app)
    {
    }
}
