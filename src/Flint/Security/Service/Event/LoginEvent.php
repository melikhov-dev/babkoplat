<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Antoxa
 * Date: 22.04.13
 * Time: 15:35
 */

namespace Flint\Security\Service\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class LoginEvent extends Event
{
    protected $user;
    public function __construct($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }


}
