<?php

use Symfony\Component\HttpKernel\Debug\ExceptionHandler;
use Symfony\Component\HttpKernel\Debug\ErrorHandler;
use MJanssen\Provider\ServiceRegisterProvider;
use MJanssen\Provider\RoutingServiceProvider;

//@todo: remove this constants after refactoring
define('KERNEL_ROOT', __DIR__);
define('APPPATH'    , __DIR__ . DIRECTORY_SEPARATOR);
define('VENDOR'     , __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR );
define('DOCROOT'    , __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR );

return function () {
    require __DIR__ . '/../vendor/autoload.php';

    $kernelDir = __DIR__;
    $config = require $kernelDir . '/cache/config.php';

    umask(0);
    ini_set('intl.default_locale', $config['intl.default_locale']);
    setlocale(LC_ALL, $config['intl.default_locale']);
    ini_set('date.timezone', $config['date.timezone']);
    date_default_timezone_set($config['date.timezone']);

    $app = new \Silex\Application($config);


    $serviceRegisterProvider = new ServiceRegisterProvider();
    $serviceRegisterProvider->registerServiceProviders($app, $app['providers']);

    $routingServiceProvider = new RoutingServiceProvider();

    $controllers = isset($config['resources']) ? $config['resources'] : [];

    if (isset($config['app.routes'])){
        $routingServiceProvider = new \MJanssen\Provider\RoutingServiceProvider();
        $routes = $config['app.routes'];
        $routingServiceProvider->addRoutes($app, $routes);
    }


    if (!empty($config['debug'])) {
        error_reporting(E_ALL | E_NOTICE | E_WARNING | E_STRICT);
        if ('cli' !== php_sapi_name()) {
            ExceptionHandler::register($config['debug']);
        }
        //DebugClassLoader::enable();
        ErrorHandler::register();
    }

//    foreach($controllers as $prefix => $controllerClass) {
//        //print_r($controllerClass);
//        $controller = new $controllerClass;
//        $app->mount($prefix, $controller);
//    }

    return $app;
};
