<?php return [
    'app.routes' => [
        [
            'pattern' => '/',
            'controller' => 'Babka\Controller\BabkaController::index',
            'method' => array('get', 'post', 'put', 'delete', 'options', 'head')
        ],
        [
            'pattern' => 'submit',
            'controller' => 'Babka\Controller\BabkaController::submit',
            'method' => array('get', 'post', 'put', 'delete', 'options', 'head')
        ],
        [
            'pattern' => 'done',
            'controller' => 'Babka\Controller\BabkaController::done',
            'method' => array('get', 'post', 'put', 'delete', 'options', 'head')
        ],
        [
            'pattern' => 'error',
            'controller' => 'Babka\Controller\BabkaController::error',
            'method' => array('get', 'post', 'put', 'delete', 'options', 'head')
        ],
        [
            'pattern' => 'about',
            'controller' => 'Babka\Controller\BabkaController::about',
            'method' => array('get', 'post', 'put', 'delete', 'options', 'head')
        ]
    ]
];
