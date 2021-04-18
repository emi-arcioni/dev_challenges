<?php
declare(strict_types=1);


use Slim\Factory\AppFactory;
use DI\Container;
use App\Handlers\HttpErrorHandler;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
$app = AppFactory::createFromContainer($container);

$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);

$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

$callableResolver = $app->getCallableResolver();
$responseFactory = $app->getResponseFactory();

$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Error Handling Middleware
$errorMiddleware = $app->addErrorMiddleware(true, false, false);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$app->run();

