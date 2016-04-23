<?php

namespace ObjectivePHP\Package\FastRoute;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use ObjectivePHP\Application\ApplicationInterface;
use ObjectivePHP\Application\Middleware\ActionMiddleware;
use ObjectivePHP\Application\Middleware\EmbeddedMiddleware;
use ObjectivePHP\Invokable\Invokable;
use ObjectivePHP\Package\FastRoute\Config\FastRoute;
use Zend\Diactoros\Response\TextResponse;

class FastRoutePackage
{
    /**
     * @var string Step on which to plug the router
     */
    protected $routingStep;

    /**
     * @var string Step on which to plug the dispatcher
     */
    protected $dispatchingStep;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    public function __construct($routingStep = 'route', $dispatchingStep = 'action')
    {
        $this->setRoutingStep($routingStep);
        $this->setDispatchingStep($dispatchingStep);
    }


    function __invoke(ApplicationInterface $app)
    {

        $app->getStep($this->getRoutingStep())->plug([$this, 'route'])->as('router');
        $app->getStep($this->getRoutingStep())->plug([$this, 'dispatch'])->as('dispatcher');

    }

    public function route(ApplicationInterface $app)
    {
        // add routes
        $routes = $app->getConfig()->subset(FastRoute::class);

        $this->dispatcher = \FastRoute\simpleDispatcher(function(RouteCollector $collector) use ($routes) {
            foreach($routes as $id => $data)
            {
                $collector->addRoute($data['method'], $data['route'], $data['handler']);
            }
        });

        // register dispatcher as a service
        $app->getServicesFactory()->registerRawService(['id' => 'fast-route.dispatcher', 'instance' => $this->dispatcher]);

    }

    public function dispatch(ApplicationInterface $app)
    {
        // Fetch method and URI from somewhere
        $httpMethod = $app->getRequest()->getMethod();
        
        $uri = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {

            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                return new TextResponse(sprintf('Requested method is not allowed. Please use one of "%s"', implode(', ', $allowedMethods)), 405);
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                $app->getRequest()->getParameters()->setRoute($vars);

                $actionMiddleware = new ActionMiddleware(Invokable::cast($handler));

                if(is_string($handler))
                {
                    $serviceId = str_replace('\\', '.', $handler);
                } else {
                    $serviceId = 'current.action';
                }
                
                $app->getStep('action')->plug($actionMiddleware);
                
                $app->setParam('runtime.action.middleware', $actionMiddleware);
                $app->setParam('runtime.action.service-id', $serviceId);
                break;

            case Dispatcher::NOT_FOUND:
            default:
                return new TextResponse('Page not found', 404);
                exit;
                break;
        }

    }

    /**
     * @return string
     */
    public function getRoutingStep()
    {
        return $this->routingStep;
    }

    /**
     * @param string $routingStep
     */
    public function setRoutingStep($routingStep)
    {
        $this->routingStep = $routingStep;
    }

    /**
     * @return string
     */
    public function getDispatchingStep()
    {
        return $this->dispatchingStep;
    }

    /**
     * @param string $dispatchingStep
     */
    public function setDispatchingStep($dispatchingStep)
    {
        $this->dispatchingStep = $dispatchingStep;
    }



}