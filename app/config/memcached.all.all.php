<?php
/**
 * Configuration for OAuth providers.
 */
return [
    'memcached_session_servers' => [
        [
            'host'    => 'localhost',
            'port' => '11211',
            'weight'   => '10'
        ],
    ],
    'memcached_servers' => [
        [
            'host'    => 'localhost',
            'port' => '11211',
            'weight'   => '10'
        ],
    ]
]
?>