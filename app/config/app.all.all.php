<?php
return [
    'intl.default_locale' => 'ru_RU',
    'date.timezone'       => 'Asia/Dubai',
    'lang'  => 'ru',
    'debug' => true,

    'includes' => [
        'Flint\Stubs\MongoClient'
    ],

    'restify.layout' => 'layout',
    'restify.layout.template' => 'index',
    'restify.layout.data_provider' => 'index.layout',

    'template.cache.path' => '%var.kernelDir%/cache/templates',
    'template.directories' => [
        '%var.kernelDir%/templates/'
    ],

    'monolog.logfile' => '%var.kernelDir%/logs/silex.log',
    'monolog.api.logfile' => '%var.kernelDir%/logs/api.log',

    'monolog.level' => \Monolog\Logger::WARNING,
    'monolog.name' => 'boombate.fep2',
];
