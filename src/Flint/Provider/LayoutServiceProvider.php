<?php
namespace Flint\Provider;

use Bb8\Service\CityManager;
use Bb8\User\UserManagerInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;


use Utils\Layouts;

class LayoutServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['index.layout'] = $app->share(function() use ($app){
            $event = $app['request']->get('event');

            $listVersion = ceil(time() / (60 * 10 + 1));

            return [
                'title'            => 'Бабкоплат',
                'meta_keywords'    => '',
                'meta_description' => '',
                'registerShow'     => false, //не айс, надо будет убрать
                'event'            => $event,
                //'user'             => $app['user'],
                'listVersion'      => $listVersion,
                'showCategory'     => false,
                'canonical'        => false
           ];
        });



    }

    public function boot(Application $app)
    {
    }

}
