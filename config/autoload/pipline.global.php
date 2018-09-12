<?php

use Zend\Expressive\Container\ApplicationFactory;

return [
    'middleware_pipeline' => [
        'errorhandler' => [
            'middleware' => [
                DocumentService\Action\SentryIO::class
            ],
            'priority' => 10000,
        ],
        'routing' => [
            'middleware' => [
                ApplicationFactory::ROUTING_MIDDLEWARE,
                ApplicationFactory::DISPATCH_MIDDLEWARE,
            ],
            'priority' => 1,
        ],
        'last' => [
            'middleware' => [
                Zend\Expressive\Middleware\NotFoundHandler::class
            ],
            'priority' => -10000,
        ],
    ],
];