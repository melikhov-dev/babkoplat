<?php
namespace Flint\ControllerExtender\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

interface AppAbstractControllerInterface
{
    public function initialize(Application $app);

    //public function before(Request $request, Application $app);
}