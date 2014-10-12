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

class SwiftMailerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['swift'] = function ($app) {
            $transport = \Swift_SmtpTransport::newInstance(
                $app['swift.config']['options']['host'], $app['swift.config']['options']['port']
            )
                ->setUsername($app['swift.config']['username'])
                ->setPassword($app['swift.config']['password']);

            return \Swift_Mailer::newInstance($transport);
        };

        $app['swift.attachment'] = function () {
            return \Swift_Attachment::newInstance();
        };

        $app['mandrill'] = $app->share(
            function ($app) {
                $mandrill =  new \Mandrill($app['swift.config']['password']);
                return $mandrill;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}


