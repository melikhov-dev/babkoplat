<?php
namespace Flint\Security\Service\Controllers;

use Bb8\Service\CityManager;
use Bb8\Service\InternalMailManager;
use Bb8\User\UserManager;
use Flint\Security\Service\Event\LoginEvent;
use Flint\Security\Service\Event\RegistrationEvent;
use OAuth\Common\Exception\Exception;
use Silex\Application;

use Stash\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Flint\Security\SecurityInterface;
use Flint\Validation\Service\Validator;
use Utils\JsonResponse;

class AuthController
{
    protected $app;
    protected $security;
    protected $validator;
    protected $urlGenerator;
    protected $mailer;
    protected $cityManager;
    protected $userManager;

    /**
     * @param Application           $app
     * @param SecurityInterface     $security
     * @param Validator             $validator
     * @param UrlGeneratorInterface $urlGenerator
     * @param InternalMailManager   $mailer
     * @param UserManager           $userManager
     */
    public function __construct(
        Application $app,
        SecurityInterface $security,
        Validator $validator,
        UrlGeneratorInterface $urlGenerator,
        InternalMailManager $mailer,
        UserManager $userManager
    )
    {
        $this->app = $app;
        $this->security = $security;
        $this->validator = $validator;
        $this->mailer = $mailer;

        $this->urlGenerator = $urlGenerator;
        $this->userManager = $userManager;

    }

    public function loginFormAction(Request $request)
    {
        if ($this->security->isLogged()) {
            return $this->app->redirect('/');
        }
        $backUrl  = ($request->get('r') ? ('?r=' . $request->get('r')) : '');

        return [
            '_template' => 'auth/loginForm2',
            'r'         => $backUrl,
            'form'      => ['username' => '', 'password' => ''],
            'errors'    => ['username' => '', 'password' => ''],
            'loginForm' => true,
        ];
    }

    public function registrationFormAction()
    {
        if ($this->security->isLogged()) {
            return $this->app->redirect('/');
        }

        return [
            '_template' => 'auth/register',
            'form'      => ['username' => '', 'password' => ''],
            'errors'    => ['username' => '', 'password' => ''],
            'loginForm' => true,
            'registerShow' => true
        ];
    }


    public function loginAction(Request $request)
    {
        // Throws exception if validation failed

        $errors = $this->validator->getErrors($request, 'user_auth', !$request->isXmlHttpRequest());

        $username = $request->get('username');
        $password = $request->get('password');
        $r        = $request->get('r');
        $backUrl  = $r ? ('?r=' . $r) : '';

        $ret = array(
            '_template' => 'auth/loginForm2',
            'loginForm' => true,
            'r'         => $r,
            'form'      => ['username' => $username, 'password' => $password],
            'errors'    => (array) $errors + ['username' => '', 'password' => '']
        );

        if (!$errors) {
            try {
                $user = $this->security->loginByCredentials($username, $password);
                if ($request->isXmlHttpRequest()) {
                    $response = new JsonResponse($this->userManager->getUserFacade());
                } else {
                    $response = new RedirectResponse($r ? $r : '/');
                }
                return $response;
            } catch (AccessDeniedHttpException $e) {
                $ret = array_merge($ret, array(
                    '_template' => 'auth/loginForm2',
                    'loginForm' => true,
                    'r'         => $backUrl,
                    'errors'    => ['username' => $e->getMessage(), 'password' => ''],
                    'error_username' => $e->getMessage(),
                    'error_password' => false
                ));
                if ($request->isXmlHttpRequest()) {
                    $ret = new JsonResponse(['message' => $e->getMessage()], 500);
                    $ret->setStatus(JsonResponse::STATUS_SYSTEM_ERROR);

                }
            }
        }
        return $ret;
    }

    public function logoutAction(Request $request)
    {
        $token = $this->security->getTokenFromCookies($request->cookies);
        if ($token) {
            $this->security->forgetToken($token);
        }

        $this->security->logout();

        return $this->app->redirect('/');
    }

    public function loginCheckAction()
    {
        return $this->security->isLogged();
    }

    public function registerfepAction(Request $request){

        $data = array(
            'username' => $request->get('email'),
            'password' => $request->get('password')?$request->get('password'): md5(time()),
            'backUri' => $request->get('r','/'),

        );

        if($request->get('city_id')){
            $data['data'] = array(
                'city_id' => $request->get('city_id')
            );
        }
        $xz = new Request($data,$data);
        $regResponse = json_decode($this->registrationAction($xz)->getContent());

        $retData = array(
            'r' =>   $request->get('r','/') . '?event=welcome',
        );
        if(isset($regResponse->message)){
            if($regResponse->message == 'Пользователь с таким email уже зарегистрирован'){
                $retData['email'] = 'Такой email уже существует';
            }else{
                $retData['email'] = $regResponse->message;
            }
        }else{
            $retData['success'] = 1;
            unset($retData['message']);
        }


        return  new JsonResponse($retData);

    }

    public function registrationAction(Request $request)
    {

        // Throws exception if validation failed
        try {
            $this->validator->validate($request, 'user_registration');
        } catch (\Exception $e) {
            $errors = json_decode($e->getMessage(), true);
            $errorMessage = [];
            foreach ($errors as $field => $_errors) {
                foreach ($_errors as $error) {
                    $errorMessage[] = $error;
                }
            }
            $ret = new JsonResponse(['message' => implode(', ', $errorMessage)]);
            $ret->setStatus(JsonResponse::STATUS_ERROR);
            return $ret;
        }

        $username = $request->get('username');
        $password = $request->get('password');

        try {
            $user = $this->security->createUser($username, $password, $request->get('data',array()));
            $this->security->loginByCredentials($username, $password);

            $dispatcher = $this->app['dispatcher'];
            $dispatcher->dispatch(SecurityInterface::REGISTRATION_EVENT, new RegistrationEvent($user));

        } catch (Exception $e) {
            if ($request->isXmlHttpRequest()) {
                $ret = new JsonResponse(['message' => $e->getMessage()]);
                $ret->setStatus(JsonResponse::STATUS_SYSTEM_ERROR);
                return $ret;
            }
        }

        if (!$request->isXmlHttpRequest()) {
            return $this->app->redirect('/?event=welcome');
        } else {
            $user['password'] = $password;
            return new JsonResponse($user);
        }

    }

    public function forgotPasswordFormAction(Request $request)
    {
        $email = $request->get('forgot_email');

        if (! $this->security->findUserBy(['email' => $email])) {
            $ret = new JsonResponse(['message' => 'Пользователь с таким email не зарегистрирован!'], 500);
            $ret->setStatus(JsonResponse::STATUS_SYSTEM_ERROR);

            return $ret;
        }

        $success = false;
        if ($token = $this->security->getResetTokenByEmail($email)) {
            $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
            $data['ResetPasswordUrl'] = 'http://' . $domain . '/auth/reset?email=' . $email . '&reset_token=' . $token;
            $success = $this->mailer->sendRestorePasswordEmail($email, $data);
        }

        return ['success' => $success];
    }

    public function resetPasswordAction(Request $request)
    {
        $resetToken = $request->get('reset_token');
        $email = $request->get('email');

        if ($user = $this->security->findUserBy(['username' => $email, 'reset_token' => $resetToken])) {
            $this->security->logout();
            $this->security->loginByUsername($email);
            return $this->app->redirect('/?event=changePassword');
        } else {
            throw new AccessDeniedHttpException();
        }
    }

    public function changePasswordFormAction(Request $request)
    {
        $password = $request->get('change_password_1');
        $success = false;
        if ($this->security->changePassword($password)) {
            $success = true;
        }
        return ['success' => $success];
    }


}
