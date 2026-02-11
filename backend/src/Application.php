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

class Application extends BaseApplication
{
    public function bootstrap(): void
    {
        // Call parent first - this loads config/bootstrap.php which defines constants
        parent::bootstrap();
        
        // Now load configuration from app.php
        $config = require CONFIG . 'app.php';
        foreach ($config as $key => $value) {
            Configure::write($key, $value);
        }

        // Register Datasource configurations with ConnectionManager
        if (Configure::check('Datasources')) {
            $datasources = Configure::consume('Datasources');
            ConnectionManager::setConfig($datasources);
        }

        // Register Cache configurations with Cache
        if (Configure::check('Cache')) {
            $cache = Configure::consume('Cache');
            foreach ($cache as $key => $config) {
                Cache::setConfig($key, $config);
            }
        }
        
        // Initialize Router with RouteCollection - REQUIRED for CakePHP 5
        Router::setRouteCollection(new RouteCollection());
        
        // Load plugins
        $this->addPlugin('Migrations');
        $this->addPlugin('Bake');
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(new ErrorHandlerMiddleware(Configure::read('Error') ?: []))
            ->add(new AssetMiddleware())
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware());

        return $middlewareQueue;
    }

    public function console(CommandCollection $commands): CommandCollection
    {
        // Load commands from plugins
        $commands = $this->pluginConsole($commands);
        
        return $commands;
    }
}
