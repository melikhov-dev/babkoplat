<?php
namespace Flint\Security\Provider;

use OAuth\Common\Storage\Session;
use Silex\Application;
use Silex\ServiceProviderInterface;

use Flint\Security\SecurityInterface;
use Flint\Security\Service\Event\LoginEvent;

use Flint\Security\Service\Security;
use Flint\Security\Service\SecurityStub;

use Flint\Security\Service\Encoder\PasswordEncoder;
use Flint\Security\Service\Adapter\UserMySqlAdapter;
use Flint\Security\Service\Adapter\OAuthTokenMySqlAdapter;

use OAuth\Common\Storage\SymfonySession as SymfonySessionStorage;
use OAuth\Common\Consumer\Credentials;

use Flint\Security\Service\Controllers\OAuthController;
use Flint\Security\Service\Controllers\AuthController;
use Flint\Security\Service\OAuth as FlintOAuth;
use Flint\Security\Service\OAuthServiceFactory;
use Flint\Security\Service\Http\Client\CurlClient;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SecurityProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['flint.security.fep.encoder'] = $app->share(function() {
            return new PasswordEncoder();
        });

        $app['flint.security.user.storage'] = $app->share(function() use ($app) {
            return new UserMySqlAdapter($app['miner.factory']);
        });

        $app['flint.security.oauth_token.storage'] = $app->share(function() use ($app) {
            return new OAuthTokenMySqlAdapter($app['miner.factory']);
        });

        $app['flint.security'] = $app->share(function() use ($app) {
            return $app[$app['security.adapter']];
        });

        $app['security.stub'] = $app->share(function() use ($app) {
            return new SecurityStub();
        });

        $app['security.real'] = $app->share(function() use ($app) {
            return new Security(
                $app['session'],
                $app['flint.security.fep.encoder'],
                $app['flint.security.user.storage'],
                $app['dispatcher'],
                $app['city.manager']
            );
        });

        $app['user'] = function() use ($app) {
            return $app['security.real']->getActiveUser();
        };

        /**
         * OAuth
         */
        $app['flint.security.oauth.service_factory'] = $app->share(function() use ($app) {
            return new OAuthServiceFactory(new CurlClient(), $app['oauth_config']['service_class_map']);
        });

        $app['flint.security.oauth.token_storage'] = $app->share(function() use ($app) {
            return new SymfonySessionStorage($app['session']);
        });

        $app['flint.security.oauth.service'] = $app->protect(function($provider, Credentials $credentials, $scope = []) use ($app) {
            /** @var $oauthServiceFactory OAuthServiceFactory */
            $oauthServiceFactory = $app['flint.security.oauth.service_factory'];
            return $oauthServiceFactory->createService($provider, $credentials, $app['flint.security.oauth.token_storage'], $scope);
        });

        $app['flint.security.oauth'] = $app->share(function() use ($app) {
            return new FlintOAuth(
                $app['flint.security.oauth.service'],
                $app['oauth_config'],
                $app['flint.security'],
                $app['mongo'],
                $app['session'],
                $app['flint.security.oauth_token.storage']
            );
        });

        $app['flint.oauth.controller'] = $app->share(function() use ($app) {
            return new OAuthController(
                $app,
                $app['url_generator'],
                $app['flint.security.oauth'],
                $app['flint.validator'],
                $app['session'],
                $app['flint.security'],
                $app['security.oauth.redirect_urls.input_email']
            );
        });

        $app['flint.auth.controller'] = $app->share(function() use ($app) {
            return new AuthController(
                $app,
                $app['flint.security'],
                $app['flint.validator'],
                $app['url_generator'],
                $app['internal.mail.manager'],
                $app['user.manager']
            );
        });


    }

    public function boot(Application $app)
    {
        // Custom validators
        \Valitron\Validator::addRule('uniqueUsername', function($value, array $params) use ($app) {
            /** @var $security Security */
            $security = $app['flint.security'];

            return $security->findUserBy(['username' => $value]) ? false : true;
        }, $app['validator.messages']['uniqueUsername']);

        \Valitron\Validator::addRule('uniqueEmail', function($value, array $params) use ($app) {
            /** @var $security Security */
            $security    = $app['flint.security'];
            $currentUser = $security->getActiveUser();
            $isValid     = false;

            if ($currentUser && $currentUser['email'] === $value) {
                $isValid = true;
            } else {
                $isValid = $security->findUserBy(['email' => $value]) ? false : true;
            }

            return $isValid;
        }, $app['validator.messages']['uniqueEmail']);

        \Valitron\Validator::addRule('usernameEmpty', function($value, array $params) use ($app) {
            $len = mb_strlen($value);
            return $len > 0;
        }, $app['validator.messages']['usernameEmpty']);

        \Valitron\Validator::addRule('passwordEmpty', function($value, array $params) use ($app) {
            $len = mb_strlen($value);
            return $len > 0;
        }, $app['validator.messages']['passwordEmpty']);

        \Valitron\Validator::addRule('usernameLength', function($value, array $params) use ($app) {
            $len = mb_strlen($value);
            return $len >= $params[0] && $len < $params[1];
        }, $app['validator.messages']['usernameLength']);

        \Valitron\Validator::addRule('passwordLength', function($value, array $params) use ($app) {
            $len = mb_strlen($value);
            return $len >= $params[0] && $len < $params[1];
        }, $app['validator.messages']['passwordLength']);

        \Valitron\Validator::addRule('validCode', function($value, array $params) use ($app) {
            /** @var \Symfony\Component\HttpFoundation\Session\Session $session  */
            $session = $app['session'];
            $codeSession = $session->get('change_phone_code');
            if ($value == $codeSession) {
                return true;
            } else {
                return false;
            }

        }, $app['validator.messages']['validCode']);
    }
}
