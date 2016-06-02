<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 02/06/2016
 * Time: 16:51
 */

namespace ObjectivePHP\Package\FastRoute;


use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use ObjectivePHP\Application\ApplicationInterface;
use ObjectivePHP\Package\FastRoute\Config\FastRoute;
use ObjectivePHP\Router\MatchedRoute;
use ObjectivePHP\Router\RouterInterface;
use ObjectivePHP\Router\RoutingResult;
use Zend\Diactoros\Response\TextResponse;

class FastRouteRouter implements RouterInterface
{
    public function route(ApplicationInterface $app) : RoutingResult
    {
        // add routes
        $routes = $app->getConfig()->subset(FastRoute::class);

        $dispatcher = \FastRoute\simpleDispatcher(function(RouteCollector $collector) use ($routes) {
            foreach($routes as $id => $data)
            {
                $collector->addRoute($data['method'], $data['route'], $data['handler']);
            }
        });


        // Fetch method and URI from somewhere
        $httpMethod = $app->getRequest()->getMethod();

        $uri = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {

            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                // FastRoute does not allow to name routes for further reference,
                // so we name matched route "anonymous" by default
                $matchedRoute = new MatchedRoute($this, 'anonymous', $handler, $vars);

                return new RoutingResult($matchedRoute);
                break;

            case Dispatcher::NOT_FOUND:
            case Dispatcher::METHOD_NOT_ALLOWED:
            default:
                return new RoutingResult();
                break;
        }

    }

    public function url($route, $params = [])
    {
        // TODO: Implement url() method.
    }
    
}