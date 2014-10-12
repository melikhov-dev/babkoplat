<?php
namespace Flint\ControllerExtender\Provider;

use Flint\ControllerExtender\Controller\AppAbstractControllerInterface;

use Flint\ControllerExtender\HttpKernel\FlintControllerResolver;
use Silex\Application;
use Silex\ServiceProviderInterface;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Utils\JsonResponse;

class AppControllerProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['resolver'] = $app->share(function () use ($app) {
            return new FlintControllerResolver($app, $app['logger']);
        });
        $app->on(KernelEvents::CONTROLLER, function (FilterControllerEvent $event) use ($app) {
            $controller = $event->getController();
            if (is_array($controller) && sizeof($controller) > 0 && $controller[0] instanceof AppAbstractControllerInterface) {
                /** @var AppAbstractControllerInterface $object */
                $object          = $controller[0];
                $object->initialize($app);
                return 'fff';
            }
        });
    }

    public function boot(Application $app)
    {
    }
}
