<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Antoxa
 * Date: 04.06.13
 * Time: 13:34
 */

namespace Flint\Security\Service\OAuth;

interface OAuthUserResultInterface{

    public function getResultUid($result);

    public function getResultEmail($result);

    public function getUserData();

    function _getUserData();

    public function requestUserInfo($path);

}