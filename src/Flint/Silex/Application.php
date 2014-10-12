<?php
namespace Flint\Silex;

use Bb8\DealsUrlMatcher;
use Silex\Application as BaseApplication;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

use PimpleAwareEventDispatcher\PimpleAwareEventDispatcher;
use Symfony\Component\HttpFoundation\Response;

class Application extends BaseApplication
{

    const PRE_RUN_EVENT = 'onPreRunEvent';

    public function __construct(array $values = array())
    {
        $tmp =  parent::__construct($values);
        $app = $this;
        $this['url_matcher'] = $this->share(function () use ($app) {
            return new DealsUrlMatcher($app['routes'], $app['request_context'],$app['mongo'],$app['city.manager'],$app['session']);
        });

        // override the standard dispatcher
        $app['dispatcher'] = $app['pimple_aware_dispatcher'] = $app->share(
            $app->extend('dispatcher', function($dispatcher) use ($app) {
                return new PimpleAwareEventDispatcher($dispatcher, $app);
            }
        ));

        return $tmp;
    }


    public function run(Request $request = null)
    {
        /** @var $dispatcher EventDispatcher */
        $dispatcher = $this['dispatcher'];
        /** @var $event PreRunEvent */
        $event = $dispatcher->dispatch(self::PRE_RUN_EVENT, new PreRunEvent($request));

        return parent::run($event->getRequest());
    }

    /**
     * remove page hash (for fb _=_)
     */
    public function redirectJs($url){
        return new Response('<script>window.location.hash = "";  window.location.href = "'.$url.'"</script>');
    }


}
