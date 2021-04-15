<?php
declare(strict_types=1);

namespace Tests\Traits;

use DI\Container;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use UnexpectedValueException;
use Slim\Factory\AppFactory;
use App\Handlers\HttpErrorHandler;
use Redis;

/**
 * App Test Trait.
 */
trait AppTestTrait
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var App
     */
    protected $app;
    
    /**
     * @var Redis
     */
    protected $redis;

    /**
     * Bootstrap app.
     *
     * @throws UnexpectedValueException
     *
     * @return void
     */
    protected function setUp(): void
    {
        $redis = new Redis();
        $redis->connect('redis', 6379);
        $this->redis = $redis;

        $container = new Container();
        $app = AppFactory::createFromContainer($container);

        $callableResolver = $app->getCallableResolver();
        $responseFactory = $app->getResponseFactory();

        $middleware = require __DIR__ . '/../../app/middleware.php';
        $middleware($app);

        $routes = require __DIR__ . '/../../app/routes.php';
        $routes($app);

        $errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);

        // Add Routing Middleware
        $app->addRoutingMiddleware();

        // Add Error Handling Middleware
        $errorMiddleware = $app->addErrorMiddleware(true, false, false);
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        $this->app = $app;
    }

    /**
     * Add mock to container.
     *
     * @param string $class The class or interface
     *
     * @return MockObject The mock
     */
    protected function mock(string $class): MockObject
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('Class not found: %s', $class));
        }

        $mock = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->set($class, $mock);

        return $mock;
    }

    /**
     * Create a server request.
     *
     * @param string $method The HTTP method
     * @param string|UriInterface $uri The URI
     * @param array $serverParams The server parameters
     *
     * @return ServerRequestInterface
     */
    protected function createRequest(
        string $method,
        $uri,
        array $serverParams = []
    ): ServerRequestInterface {
        return (new ServerRequestFactory())->createServerRequest($method, $uri, $serverParams);
    }

    /**
     * Create a JSON request.
     *
     * @param string $method The HTTP method
     * @param string|UriInterface $uri The URI
     * @param array|null $data The json data
     *
     * @return ServerRequestInterface
     */
    protected function createJsonRequest(
        string $method,
        $uri,
        array $data = null
    ): ServerRequestInterface {
        $request = $this->createRequest($method, $uri);

        if ($data !== null) {
            $request = $request->withParsedBody($data);
        }

        return $request->withHeader('Content-Type', 'application/json');
    }

 /**
     * Verify that the given array is an exact match for the JSON returned.
     *
     * @param array $expected The expected array
     * @param ResponseInterface $response The response
     *
     * @throws JsonException
     * @return void
     */
    protected function assertJsonData(array $expected, ResponseInterface $response): void
    {
        $actual = (string)$response->getBody();
        $this->assertSame($expected, (array)json_decode($actual, true, 512, JSON_THROW_ON_ERROR));
    }
}