<?php

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin('Assets', ['path' => '/'], function (RouteBuilder $routeBuilder) {
    $routeBuilder->prefix('Admin', function (RouteBuilder $routeBuilder) {
        $routeBuilder->extensions(['json']);

        $routeBuilder->connect('/attachments/:action/*', ['controller' => 'Attachments']);
    });
});
