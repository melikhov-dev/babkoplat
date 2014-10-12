<?php
namespace Flint\Provider;

use Bb8\DealsUrlMatcher;
use Flint\Silex\PreRunEvent;
use Silex\ServiceProviderInterface;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;

class RouterServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {

        /** @var $dispatcher EventDispatcher */
        /*$dispatcher = $app['dispatcher'];

        $dispatcher->addListener(\Flint\Silex\Application::PRE_RUN_EVENT, function (PreRunEvent $event) {
            if ($_SERVER['REQUEST_URI'] == '/') {
                $_SERVER['REQUEST_URI'] = '/deals/54331';
            }
        });*/



        /*$dispatcher->addListener(KernelEvents::REQUEST, function (GetResponseEvent $event) use($app) {
            $request = $event->getRequest();
            $matcher = new DealsUrlMatcher($app['routes'], $app['request_context']);
            $matcher->matchRequest($request);
        });*/

        //$dispatcher->addSubscriber(new RouterListener(new DealsUrlMatcher($app['routes'], $app['request_context']), $app['request_context'], $app['logger']));



        /* $app->before(function(Request $request){
             if($request->getRequestUri() == '/'){
                 $_SERVER['REQUEST_URI'] = '/deals/';
                 $app['request'] = new Request($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);
             }

         });*/

    }

    public function boot(Application $app)
    {
    }
}
