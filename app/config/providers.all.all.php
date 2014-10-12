<?php
return [
    'providers' => [
        ['class' => 'Flint\Provider\TemplateServiceProvider'],
        ['class' => 'Flint\Provider\RestifyServiceProvider'],
        ['class' => 'Flint\Provider\LayoutServiceProvider'],
        ['class' => 'Silex\Provider\SessionServiceProvider'],
        ['class' => 'Flint\ControllerExtender\Provider\AppControllerProvider']
    ]
];
