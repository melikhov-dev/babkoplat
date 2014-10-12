<?php
namespace Flint\Security\Provider;

use Silex\Application;
use Silex\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Flint\Security\Service\Controllers\OAuthController;
use Flint\Security\Service\Security;

class OAuthControllerProvider implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        /** @var $controller Application */
        $controller = $app['controllers_factory'];

        /**
         * Callback supposed to use for creating cookie for User just logged in the system
         *
         * @param Request  $request
         * @param Response $response
         *
         * @return Response
         */
        $createCookieForLoggedUser = function (Request $request, Response $response) use ($app) {
            /** @var $security Security */
            $security = $app['flint.security'];

            if ($security->isLogged()) {
                /**
                 * If login action succeed, create User token
                 * and set cookie to the browser
                 */
                $cookie = $security->createToken($request->server->get('HTTP_USER_AGENT'));

                $response->headers->setCookie($cookie);
            }

            return $response;
        };

        $controller->post('/email', 'flint.oauth.controller:emailFormAction');
        $controller->post('/email/send_confirm', 'flint.oauth.controller:sendConfirmOAuthEmailAction');
        $controller->get('/email/confirm', 'flint.oauth.controller:doConfirmOAuthAction');

        $controller->get('/twitter/login', 'flint.oauth.controller:twitterLoginAction');
        $controller->get('/twitter/unassociate', 'flint.oauth.controller:twitterUnassociateAction');
        $controller->get('/twitter/auth', 'flint.oauth.controller:twitterAuthAction')
            ->bind('twitter_auth_callback_url')
            ->after($createCookieForLoggedUser);

        $controller->get('/facebook/login', 'flint.oauth.controller:facebookLoginAction');
        $controller->get('/facebook/unassociate', 'flint.oauth.controller:facebookUnassociateAction');
        $controller->get('/facebook/auth', 'flint.oauth.controller:facebookAuthAction')
            ->bind('facebook_auth_callback_url')
            ->after($createCookieForLoggedUser);

        $controller->get('/vkontakte/login', 'flint.oauth.controller:vkontakteLoginAction');
        $controller->get('/vkontakte/unassociate', 'flint.oauth.controller:vkontakteUnassociateAction');
        $controller->get('/vkontakte/auth', 'flint.oauth.controller:vkontakteAuthAction')
            ->bind('vkontakte_auth_callback_url')
            ->after($createCookieForLoggedUser);

        $controller->get('/mailru/login', 'flint.oauth.controller:mailruLoginAction');
        $controller->get('/mailru/unassociate', 'flint.oauth.controller:mailruUnassociateAction');
        $controller->get('/mailru/auth', 'flint.oauth.controller:mailruAuthAction')
            ->bind('mailru_auth_callback_url')
            ->after($createCookieForLoggedUser);

        return $controller;
    }
}
