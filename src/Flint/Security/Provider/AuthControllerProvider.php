<?php
namespace Flint\Security\Provider;

use Flint\Security\SecurityInterface;
use Flint\Security\Service\Event\RegistrationEvent;
use Silex\Application;
use Silex\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Flint\Security\Service\Security;

class AuthControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        /** @var $controller Application */
        $controller = $app['controllers_factory'];

        /**
         * This function executes before all another actions
         * and it serves to autologin User by cookie,
         * or to remove invalid User token from storage
         */
        $app->before(function (Request $request) use ($app) {
            /** @var $security Security */
            $security = $app['flint.security'];

            if (!$security->isLogged()) {
                $token = $security->getTokenFromCookies($request->cookies);

                if ($token) {
                    try {
                        $security->loginByToken($token);
                    } catch (AccessDeniedHttpException $e) {
                        // [a.chernykh] Специально не забываем куку
                        //$security->forgetToken($token);
                    }
                }
            }
        }, Application::EARLY_EVENT);

        /**
         * This function executes after all another actions
         * and removes invalid cookie from browser if User not logged in
         */
        $app->after(function (Request $request, Response $response) use ($app) {
            /** @var $security Security */
            $security = $app['flint.security'];

            // Remove 'authautologin' cookie if user was not autologged on
            if (!$security->isLogged()) {
                $cookie = $request->cookies->get('authautologin');

                if ($cookie) {
                    // [a.chernykh] Специально не забываем куку
                    //$response->headers->clearCookie('authautologin', '/', '.boombate.com');
                }
            }

            return $response;
        }, Application::LATE_EVENT);

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

        /**
         * Callback supposed to use for removing cookie for User just logged out from the system
         *
         * @param Request  $request
         * @param Response $response
         *
         * @return Response
         */
        $afterLogoutClean = function (Request $request, Response $response) use ($app) {
            /** @var $security Security */
            $security = $app['flint.security'];

            if (!$security->isLogged()) {
                $cookie = $request->cookies->get('authautologin');

                if ($cookie) {
                    $response->headers->clearCookie('authautologin', '/', '.boombate.com');
                }
            }

            return $response;
        };

        $controller->post('/forgotPassword', 'flint.auth.controller:forgotPasswordFormAction');
        $controller->get('/login', 'flint.auth.controller:loginFormAction');
        $controller->post('/login', 'flint.auth.controller:loginAction')
            ->after($createCookieForLoggedUser);

        $controller->get('/reset', 'flint.auth.controller:resetPasswordAction');
        $controller->post('/changePassword', 'flint.auth.controller:changePasswordFormAction');

        $controller->get('/registration', 'flint.auth.controller:registrationFormAction');
        $controller->post('/registration', 'flint.auth.controller:registrationAction')
            ->after($createCookieForLoggedUser);

        $controller->post('/registerfep', 'flint.auth.controller:registerfepAction')
            ->after($createCookieForLoggedUser);

        $controller->get('/login/check', 'flint.auth.controller:loginCheckAction');
        $controller->get('/logout', 'flint.auth.controller:logoutAction')
            ->after($afterLogoutClean);

        /** @var $dispatcher EventDispatcher */
        /*
         * example
         *
         $dispatcher = $app['dispatcher'];

        $dispatcher->addListener(SecurityInterface::LOGIN_EVENT, function (LoginEvent $event) {
            var_dump($event->getUser());
            die('xz');
        });*/

        $dispatcher = $app['dispatcher'];
        $dispatcher->addListener(SecurityInterface::REGISTRATION_EVENT, function (RegistrationEvent $event) {
            //var_dump($event->getUser());

        });



        return $controller;
    }
}
