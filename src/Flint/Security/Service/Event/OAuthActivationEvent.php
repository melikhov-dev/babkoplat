<?php
namespace Flint\Security\Service\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class OAuthActivationEvent extends Event
{
    protected $uid;
    protected $provider;
    protected $email;
    protected $confirmationToken;

    public function __construct($email, $uid, $provider, $confirmationToken)
    {
        $this->email = $email;
        $this->uid   = $uid;
        $this->provider = $provider;
        $this->confirmationToken = $confirmationToken;
    }

    public function getData()
    {
        return [
            'email'    => $this->email,
            'uid'      => $this->uid,
            'provider' => $this->provider,
            'token'    => $this->confirmationToken
        ];
    }
}
