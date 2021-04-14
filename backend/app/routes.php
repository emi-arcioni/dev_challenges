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
        $group->post('/{id}/vote', IssueController::class . ':vote');
    });
};
