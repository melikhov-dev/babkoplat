<?php
namespace Flint\Security\Service\Controllers;

use Flint\Security\SecurityInterface;
use Flint\Security\Service\Event\RegistrationEvent;
use Flint\Security\Service\Event\OAuthActivationEvent;
use Silex\Application;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use Flint\Security\Service\Security;
use Flint\Security\Service\OAuth as FlintOAuth;
use Flint\Validation\Service\Validator;

class OAuthController
{
    protected $app;
    protected $urlGenerator;
    protected $oauth;
    protected $validator;
    protected $session;
    protected $security;
    protected $inputEmailTemplate;

    /**
     * @param Application           $app
     * @param UrlGeneratorInterface $urlGenerator
     * @param FlintOAuth            $oauth
     * @param Validator             $validator
     * @param SessionInterface      $session
     * @param SecurityInterface     $security
     * @param string                $inputEmailTemplate
     */
    public function __construct(
        Application $app,
        UrlGeneratorInterface $urlGenerator,
        FlintOAuth $oauth,
        Validator $validator,
        SessionInterface $session,
        SecurityInterface $security,
        $inputEmailTemplate
    ) {
        $this->app          = $app;
        $this->urlGenerator = $urlGenerator;
        $this->validator    = $validator;
        $this->session      = $session;
        $this->oauth        = $oauth;
        $this->security     = $security;

        $this->inputEmailTemplate = $inputEmailTemplate;
    }

    /**
     * Helper function for authorization via OAuth2
     *
     * @param string  $provider
     * @param Request $request
     * @param string  $callbackUrlName
     *
     * @return array
     */
    protected function oauth2Login($provider, Request $request, $callbackUrlName)
    {
        $callbackUrl   = $this->getAbsoluteUrl($callbackUrlName);

        $oauthToken    = $request->get('code');

        $res =  $this->oauth->authorize($provider, $callbackUrl, $oauthToken);

        if(is_array($res) && isset($res['register']) && $res['register']){
            $dispatcher = $this->app['dispatcher'];
            $dispatcher->dispatch(SecurityInterface::REGISTRATION_EVENT, new RegistrationEvent($res['user']));
            return true;
        }
        return $res;
    }

    protected function getAbsoluteUrl($name)
    {
        return $this->urlGenerator->generate($name, [],UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function doConfirmOAuthAction(Request $request)
    {
        $email    = $request->get('email');
        $uid      = $request->get('uid');
        $provider = $request->get('provider');
        $token    = $request->get('token');

        $oauthData = $this->oauth->getConfirmEmailToken($email, $uid, $provider, $token);
        if (!$oauthData) {
            return $this->app->redirectJs('/?event=oauthDenied');
        }

        $user = $this->security->findUserBy(['username' => $email]);
        if (!$user) {
            return $this->app->redirectJs('/?event=oauthDenied');
        }

        $res = $this->oauth->loginUser($oauthData['uid'], $email, $oauthData['token'], $oauthData['provider'], $oauthData['userData']);
        if (is_array($res) && isset($res['register']) && $res['register']) {
            $dispatcher = $this->app['dispatcher'];
            $dispatcher->dispatch(SecurityInterface::REGISTRATION_EVENT, new RegistrationEvent($res['user']));
        }

        /** @var $security Security */
        $security = $this->app['security.real'];
        $security->activateUser($user['id']);

        $this->oauth->removeConfirmEmailToken($email, $uid, $provider, $token);

        return $this->app->redirectJs('/?event=oauthConfirmed');
    }

    public function sendConfirmOAuthEmailAction()
    {
        $oauthData = $this->oauth->retrieveOAuthData();
        $email     = $this->session->get('oauth_email');

        if ($oauthData && $email) {
            $user = $this->security->findUserBy(['username' => $email]);

            if ($user) {
                $dispatcher = $this->app['dispatcher'];
                $dispatcher->dispatch(
                    SecurityInterface::SENDOAUTHACTIVATION_EVENT,
                    new OAuthActivationEvent($email, $oauthData['uid'], $oauthData['provider'], $oauthData['token'])
                );

                // NS-434. We save data of the confirmation email in a mongo storage
                // for the situation if we open confirmation link in different browser
                $this->oauth->saveConfirmEmailToken(
                    $email,
                    $oauthData['uid'],
                    $oauthData['provider'],
                    $oauthData['token'],
                    $oauthData['userData']
                );

                $this->session->remove('oauth_email');

                return new JsonResponse('Email sent');
            }
        }

        $this->session->remove('oauth_email');

        return new JsonResponse(['message' => 'Authorization error'], 403);
    }

    /**
     * Processing of email form
     * It is needed if OAuth provider haven't gave email of the User
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function emailFormAction(Request $request)
    {
        $email = $request->get('new_email');

        $this->validator->validate($request, 'oauth_email');

        $oauthData = $this->oauth->retrieveOAuthData();

        if($oauthData) {
            $user = $this->security->findUserBy(['username' => $email]);

            if ($user) {
                $this->session->set('oauth_email', $email);

                return new JsonResponse(['action' => 'oauthConfirm'], 403);
            } else {
                $res = $this->oauth->loginUser($oauthData['uid'], $email, $oauthData['token'], $oauthData['provider'], $oauthData['userData']);
                if(is_array($res) && isset($res['register']) && $res['register']){
                    $dispatcher = $this->app['dispatcher'];
                    $dispatcher->dispatch(SecurityInterface::REGISTRATION_EVENT, new RegistrationEvent($res['user']));

                    return true;
                }
            }
        } else {
            throw new HttpException(403, 'Authorization error');
        }

        return true;
    }

    //==========
    // Twitter
    //==========

    public function twitterLoginAction(Request $request)
    {
        $callbackUrl = $this->getAbsoluteUrl('twitter_auth_callback_url') . '?backUri=' . $request->get('backUri', '/');

        return $this->app->redirect((string) $this->oauth->getAuthorizationUri('twitter', $callbackUrl));
    }

    public function twitterAuthAction(Request $request)
    {
        $backUri = $request->get('backUri', '/');

        $callbackUrl = $this->getAbsoluteUrl('twitter_auth_callback_url');
        $oauthToken    = $request->get('oauth_token');
        $oauthVerifier = $request->get('oauth_verifier');

        $result = $this->oauth->authorize('twitter', $callbackUrl, $oauthToken, $oauthVerifier);

        return $result
            ? $this->app->redirect($backUri)
            : $this->app->redirectJs(strtr($this->inputEmailTemplate, [':backUri' => $backUri, ':provider' => 'twitter']));
    }

    public function twitterUnassociateAction()
    {
        $this->oauth->unassociateSocialNetwork('twitter');

        return new \Symfony\Component\HttpFoundation\JsonResponse();
    }

    //===========
    // Facebook
    //===========

    public function facebookLoginAction(Request $request)
    {
        $this->session->set('facebookBackUri', $request->get('backUri', '/'));

        $callbackUrl = $this->getAbsoluteUrl('facebook_auth_callback_url');

        return $this->app->redirect((string) $this->oauth->getAuthorizationUri('facebook', $callbackUrl));
    }

    public function facebookAuthAction(Request $request)
    {
        $backUri = $this->session->get('facebookBackUri', '/');
        $result  = $this->oauth2Login('facebook', $request, 'facebook_auth_callback_url', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->session->remove('facebookBackUri');

        return $result
            ? $this->app->redirectJs($backUri) //фейс бук оставляет после авторизации хеш, который ломает дальше все роуты
            : $this->app->redirectJs(strtr($this->inputEmailTemplate, [':backUri' => $backUri, ':provider' => 'facebook']));
    }

    public function facebookUnassociateAction()
    {
        $this->oauth->unassociateSocialNetwork('facebook');

        return new \Symfony\Component\HttpFoundation\JsonResponse();
    }

    //==========
    // Mail.ru
    //==========

    public function mailruLoginAction(Request $request)
    {
        $this->session->set('mailruBackUri', $request->get('backUri', '/'));

        $callbackUrl = $this->getAbsoluteUrl('mailru_auth_callback_url');

        return $this->app->redirect((string) $this->oauth->getAuthorizationUri('mailru', $callbackUrl));
    }

    public function mailruAuthAction(Request $request)
    {
        $backUri = $this->session->get('mailruBackUri', '/');
        $result  = $this->oauth2Login('mailru', $request, 'mailru_auth_callback_url');

        return $result
            ? $this->app->redirect($backUri)
            : $this->app->redirectJs(strtr($this->inputEmailTemplate, [':backUri' => $backUri, ':provider' => 'facebook']));
    }

    public function mailruUnassociateAction()
    {
        $this->oauth->unassociateSocialNetwork('mailru');

        return new \Symfony\Component\HttpFoundation\JsonResponse();
    }

    //============
    // VKontakte
    //============

    public function vkontakteLoginAction(Request $request)
    {
        $this->session->set('vkontakteBackUri', $request->get('backUri', '/'));

        $callbackUrl = $this->getAbsoluteUrl('vkontakte_auth_callback_url');

        return $this->app->redirect((string) $this->oauth->getAuthorizationUri('vkontakte', $callbackUrl));
    }

    public function vkontakteAuthAction(Request $request)
    {
        $backUri = $this->session->get('vkontakteBackUri', '/');
        $result  = $this->oauth2Login('vkontakte', $request, 'vkontakte_auth_callback_url');

        return $result
            ? $this->app->redirect($backUri)
            : $this->app->redirectJs(strtr($this->inputEmailTemplate, [':backUri' => $backUri, ':provider' => 'vkontakte']));
    }

    public function vkontakteUnassociateAction()
    {
        $this->oauth->unassociateSocialNetwork('vkontakte');

        return new \Symfony\Component\HttpFoundation\JsonResponse();
    }
}
