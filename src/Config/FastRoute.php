<?php

namespace ObjectivePHP\Package\FastRoute\Config;

use ObjectivePHP\Config\SingleValueDirectiveGroup;
use ObjectivePHP\Package\FastRoute\Exception;

class FastRoute extends SingleValueDirectiveGroup
{
    const GET = 1;
    const POST = 2;
    const PUT = 4;
    const DELETE = 8;

    /**
     * FastRoute constructor.
     * @param $identifier   string  Route name
     * @param $route        string  Actual route
     * @param null $action  string  Action Middleware class name
     * @param int $method
     */
    public function __construct($identifier, $route, $action = null, $method = self::GET)
    {

        if(is_null($action))
        {
            throw new Exception(sprintf('Action cannot be null (route "%s")', $identifier));
        }

        if(is_int($method))
        {
            $method = $this->extractMethods($method); 
        }


        $value = ['id' => $identifier, 'route' => $route, 'method' => $method, 'handler' => $action];

        parent::__construct($identifier, $value);
    }

    /**
     * @param int $flag
     * @return array
     */
    protected function extractMethods(int $flag)
    {
        $methods = [];

        foreach ([1 => 'GET', 2 => 'POST', 4 => 'PUT', 8 => 'DELETE'] as $id => $method)
        {
            if($flag & $id) $methods[] = $method;
        }
        
        return $methods;
    }
}