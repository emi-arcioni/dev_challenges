<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

use App\Controllers\IssueController;

return function (Slim\App $app) {
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('API is working');
        return $response;
    });

    $app->group('/issues', function (Group $group) {
        $group->get('', IssueController::class . ':index');
        $group->get('/{id}', IssueController::class . ':get');
        $group->post('/{id}/join', IssueController::class . ':join');
        $group->post('/{id}/leave', IssueController::class . ':leave');
        $group->post('/{id}/vote', IssueController::class . ':vote');
    });


    // -- workaround to avoid CORS issues --
    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });
    
    $app->add(function ($request, $handler) {
        $response = $handler->handle($request);
        return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    });

    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
        throw new HttpNotFoundException($request);
    });
    // --
};
