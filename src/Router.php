<?php

namespace hctorres02\router;

class Router
{
    /**
     * @var array
     */
    private static $routes = [];

    /**
     * @var callable|null
     */
    private static $pathNotFound = null;

    /**
     * @var callable|null
     */
    private static $methodNotAllowed = null;

    /**
     * @var callable|null
     */
    private static $middleware = null;

    /**
     * @param string $expr
     * @param string $controller
     * @param array $actions
     */
    public static function mix($expr, $controller, $actions): void
    {
        $verbs = ['get', 'post', 'put', 'delete'];

        foreach ($actions as $k => $action) {
            $handle = implode('::', [$controller, $action]);
            $method = $verbs[$k];

            self::add($expr, $handle, $method);
        }
    }

    /**
     * @param callable $middleware
     * @param callable $handle
     * @return void
     */
    public static function addMiddleware($middleware, $handle): void
    {
        self::$middleware = $middleware;

        call_user_func($handle);

        self::$middleware = null;
    }

    /**
     * @param string $expr
     * @param callable|string $controller
     * @param string $action
     */
    public static function get($expr, $controller, $action = null): void
    {
        if ($action) {
            $controller = implode('::', [$controller, $action]);
        }

        self::add($expr, $controller, 'get');
    }

    /**
     * @param string $expr
     * @param callable|string $controller
     * @param string $action
     * @return void
     */
    public static function post($expr, $controller, $action = null): void
    {
        if ($action) {
            $controller = implode('::', [$controller, $action]);
        }

        self::add($expr, $controller, 'post');
    }

    /**
     * @param callable|string $controller
     * @param string $action
     * @return void
     */
    public static function pathNotFound($controller, $action = null): void
    {
        if ($action) {
            $controller = implode('::', [$controller, $action]);
        }

        self::$pathNotFound = $controller;
    }

    /**
     * @param callable|string $controller
     * @param string $action
     * @return void
     */
    public static function methodNotAllowed($controller, $action = null): void
    {
        if ($action) {
            $controller = implode('::', [$controller, $action]);
        }

        self::$methodNotAllowed = $controller;
    }

    /**
     * @param string $string
     * @param string $path
     * @param string $basepath
     * @return void
     */
    public static function run($method, $path, $basepath = '/'): void
    {
        $path_match_found = false;
        $route_match_found = false;
        $method = strtolower($method);

        foreach (self::$routes as $route) {
            $expr = $route['expr'];

            if ($basepath != '' && $basepath != '/') {
                $expr = "({$basepath}){$expr}";
            }

            if (!preg_match('#^' . $expr . '$#', $path, $matches)) {
                continue;
            }

            $path_match_found = true;

            if ($route['method'] != $method) {
                continue;
            }

            $route_match_found = true;
            array_shift($matches);

            if ($basepath != '' && $basepath != '/') {
                array_shift($matches);
            }

            if ($route['middleware']) {
                call_user_func_array($route['middleware'], $matches);
            }

            call_user_func_array($route['handle'], $matches);
            return;
        }

        if ($route_match_found) {
            return;
        }

        if (!$path_match_found) {
            http_response_code(404);

            if (self::$pathNotFound) {
                call_user_func_array(self::$pathNotFound, [$path]);
            }

            return;
        }

        http_response_code(405);

        if (self::$methodNotAllowed) {
            call_user_func_array(self::$methodNotAllowed, [$path, $method]);
        }
    }

    /**
     * @param string $expr
     * @param callable $handle
     * @param string $method
     * @return void
     */
    private static function add(string $expr, callable $handle, string $method): void
    {
        array_push(self::$routes, [
            'expr' => $expr,
            'handle' => $handle,
            'method' => $method,
            'middleware' => self::$middleware
        ]);
    }
}
