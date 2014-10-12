<?php
namespace Flint\Security;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Security interface
 */
interface SecurityInterface
{
    const LOGIN_EVENT               = 'user_login_event';
    const REGISTRATION_EVENT        = 'user_registration_event';
    const SENDACTIVATION_EVENT      = 'send_activation_event';
    const SENDOAUTHACTIVATION_EVENT = 'send_oauth_activation_event';

    /**
     * @param $token
     *
     * @return array
     */
    function findUserByToken($token);

    /**
     * @param array $criteria
     *
     * @return mixed
     */
    function findUserBy(array $criteria);

    /**
     * @return int
     */
    function getActiveUserId();

    /**
     * @return array|null
     */
    function getActiveUser();

    /**
     * @param array $user
     *
     * @return void
     */
    function rememberUser($user);

    /**
     * @param string $login
     * @param string $password
     *
     * @return array
     */
    function loginByCredentials($login, $password);

    /**
     * @param string $username
     *
     * @return array
     */
    function loginByUsername($username);

    /**
     * @param string $token
     *
     * @return array
     */
    function loginByToken($token);

    /**
     * @param string $token
     *
     * @return array
     */
    function loginByResetToken($token);

    /**
     * @return void
     */
    function logout();

    /**
     * Has user role ?
     *
     * @param string|array $role
     * @param integer|null $userId null - for current user
     *
     * @return boolean
     */
    function hasRole($role, $userId = null);

    /**
     * Is user is not anonymous
     *
     * @return boolean
     */
    function isLogged();

    /**
     * Create and save token
     *
     * @param int    $userId
     * @param string $userAgent
     *
     * @return Cookie
     */
    function createToken($userAgent, $userId = null);

    /**
     * Delete token from storage
     *
     * @param string $token
     *
     * @return mixed
     */
    function forgetToken($token);

    /**
     * @param ParameterBag $cookies
     *
     * @return string
     */
    function getTokenFromCookies(ParameterBag $cookies);

    /**
     * Create new User
     *
     * @param string $username
     * @param string|null $plainPassword It is nullable in case when we register user by OAuth
     *
     * @return void
     */
    function createUser($username, $plainPassword = null, $data = array());

    /**
     * Update existing User
     *
     * @param array $data
     *
     * @return bool
     */
    function updateUser($data);

    /**
     * activate user for everyday newsletter
     *
     * @param $userId
     *
     * @return bool
     */
    function activateUser($userId);

    function getResetTokenByEmail($email);

    function changePassword($password);

    function getSocialNetworkAssociations();

    function getUserByOAuthUid($providerName, $uid);

    function changeEmail($email);

    function isUserSubscribed();

    function activateNumber($phone);

    function getCouponCountForUser($userId);

    function getReviewCountForUser($userId);
}
