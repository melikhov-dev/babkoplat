<?php
namespace Foo\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class FooController implements ControllerProviderInterface
{
    private $app;
    private $user;
    private $domen = 'http://old.boombate.com';


    /**
     * @return ApiService
     */
    protected function service(){
        return $this->app['api.service'];
    }

    public function connect(Application $app)
    {
        $this->app = $app;
        /** @var $controller Application */
        $controller = $app['controllers_factory'];
        return $controller;
    }

    public function fooAction()
    {
        return new Response('Ok');
    }
}
