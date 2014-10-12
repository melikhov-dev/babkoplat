<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Antoxa
 * Date: 22.04.13
 * Time: 15:35
 */

namespace Flint\Silex;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class PreRunEvent extends Event
{
    protected $request;

    public function __construct(Request &$request = null)
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
}
