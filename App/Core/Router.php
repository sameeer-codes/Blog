<?php

namespace App\Core;
class Router
{
    private array $routes;
    private $lastRouteAdded;

    public function addRoute($url, $controller, $method, $dependency = [])
    {
        $this->routes[] = [
            'url' => $url,
            'controller' => $controller,
            'method' => $method,
            'di' => $dependency,
            'middleware' => [],
        ];

        $this->lastRouteAdded = array_key_last($this->routes);
        return $this;
    }

    public function get($url, $controller, $di = [])
    {
        return $this->addRoute($url, $controller, "get", $di, );
    }
    public function post($url, $controller, $di = [])
    {
        return $this->addRoute($url, $controller, "post", $di, );
    }
    public function put($url, $controller, $di = [])
    {
        return $this->addRoute($url, $controller, "put", $di, );
    }
    public function patch($url, $controller, $di = [])
    {
        return $this->addRoute($url, $controller, "patch", $di, );
    }

    public function delete($url, $controller, $di = [])
    {
        return $this->addRoute($url, $controller, 'delete', $di, );
    }

    public function attachMiddleware($middlewares = [])
    {
        $this->routes[$this->lastRouteAdded]['middleware'] = $middlewares;
    }

    public function routeToController(string $url, $method = 'get')
    {
        foreach ($this->routes as $routeIndex => $route) {
            if ($url === $route['url'] && strtoupper($method) === strtoupper($route['method'])) {
                if (!empty($route['middleware'])) {
                    foreach ($route['middleware'] as $index => $middleware) {
                        $container = require correctPath('/setContainers.php');
                        $middlewareContainer = $container->getService('Middleware');
                        $middlewareContainer->handle($middleware);
                    }
                }
                [$class, $method] = $route['controller'];
                if (class_exists($class) && method_exists($class, $method)) {
                    $di = $route['di'];
                    if (!empty($di)) {
                        $class = new $class(...$di);
                    } else {
                        $class = new $class();
                    }
                    $class->$method();
                } else {
                    echo $class . ' or ' . $method . ' Not Found';
                }
                return;
            } else if ($url === $route['url'] && strtoupper($method) != strtoupper($route['method'])) {
                sendResponse("error", 405, "Invalid Request Method");
            }
        }
        sendResponse("error", 404, "Route not found for $url");
    }
}