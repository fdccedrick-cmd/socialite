<?php
declare(strict_types=1);

namespace App;

use Cake\Cache\Cache;
use Cake\Console\CommandCollection;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Core\Exception\MissingPluginException;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\RouteCollection;
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Psr\Http\Message\ServerRequestInterface;

class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{
    public function bootstrap(): void
    {
        // Call parent to load config/bootstrap.php which defines constants and loads configuration
        parent::bootstrap();
        
        // Initialize Router with RouteCollection - REQUIRED for CakePHP 5
        Router::setRouteCollection(new RouteCollection());
        
        // Load plugins only if they exist (dev environment)
        // In production, migrations/bake are not needed since we use init-db.sql
        if (class_exists('\\Migrations\\Plugin')) {
            $this->addPlugin('Migrations');
        }
        if (class_exists('\\Bake\\Plugin')) {
            $this->addPlugin('Bake');
        }
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(new ErrorHandlerMiddleware(Configure::read('Error') ?: []))
            ->add(new AssetMiddleware())
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware())
            ->add(new AuthenticationMiddleware($this));

        return $middlewareQueue;
    }

    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $authenticationService = new AuthenticationService([
            'unauthenticatedRedirect' => '/login',
            'queryParam' => 'redirect',
        ]);

        // Load authenticators
        $authenticationService->loadAuthenticator('Authentication.Session');
        $authenticationService->loadAuthenticator('Authentication.Form', [
            'fields' => [
                'username' => 'username',
                'password' => 'password',
            ],
            'loginUrl' => '/login',
        ]);

        // Load identifiers
        $authenticationService->loadIdentifier('Authentication.Password', [
            'fields' => [
                'username' => 'username',
                'password' => 'password_hash',
            ],
        ]);

        return $authenticationService;
    }

    public function routes(RouteBuilder $routes): void
    {
        $routes->setRouteClass('Cake\Routing\Route\DashedRoute');
        
        // Execute the routes configuration
        $routesClosure = require CONFIG . 'routes.php';
        $routesClosure($routes);
    }

    public function console(CommandCollection $commands): CommandCollection
    {
        // Load commands from plugins
        $commands = $this->pluginConsole($commands);
        
        return $commands;
    }
}
