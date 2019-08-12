<?php
namespace hyf\component\route;

/**
 * 修改自 Macaw 类
 * 添加对组、组中间件和中间件的支持
 * 修改直接输出方式为return返回方式
 *
 * @method static routerHandler get(string $route, Callable $callback)
 * @method static routerHandler post(string $route, Callable $callback)
 * @method static routerHandler put(string $route, Callable $callback)
 * @method static routerHandler delete(string $route, Callable $callback)
 * @method static routerHandler options(string $route, Callable $callback)
 * @method static routerHandler head(string $route, Callable $callback)
 */
class routerHandler
{

    public static $halts = false;

    public static $routes = [];

    public static $methods = [];

    public static $callbacks = [];

    public static $middleware = [];

    public static $maps = [];

    public static $patterns = [
        ':any' => '[^/]+', 
        ':num' => '[0-9]+', 
        ':all' => '.*'
    ];

    public static $error_callback;

    public static $group = '';

    public static $group_middleware = null;

    public static function group(...$params)
    {
        $uri = $params[0];
        if (count($params) == 2) {
            $middleware = null;
            $callback = $params[1];
        } elseif (count($params) == 3) {
            $middleware = $params[1];
            $callback = $params[2];
        }
        self::$group = rtrim((strpos($uri, '/') === 0 ? $uri : '/' . $uri), '/');
        if (is_object($middleware) || is_null($middleware)) {
            self::$group_middleware = $middleware;
        } else {
            self::$group_middleware = "\\application\\" . app_name() . "\\middleware\\" . $middleware;
        }
        $callback(self::class);
        self::$group = '';
        self::$group_middleware = null;
    }

    /**
     * Defines a route w/ callback and method
     */
    public static function __callStatic($method, $params)
    {
        if ($method == 'map') {
            $maps = array_map('strtoupper', $params[0]);
            $uri = strpos($params[1], '/') === 0 ? $params[1] : '/' . $params[1];
            if (count($params) == 3) {
                $middleware = null;
                $callback = $params[2];
            } elseif (count($params) == 4) {
                $middleware = $params[2];
                $callback = $params[3];
            }
        } else {
            $maps = null;
            $uri = strpos($params[0], '/') === 0 ? $params[0] : '/' . $params[0];
            $callback = $params[1];
            if (count($params) == 2) {
                $middleware = null;
                $callback = $params[1];
            } elseif (count($params) == 3) {
                $middleware = $params[1];
                $callback = $params[2];
            }
        }
        
        array_push(self::$maps, $maps);
        array_push(self::$routes, self::$group . $uri);
        array_push(self::$methods, strtoupper($method));
        if (is_object($middleware) || is_null($middleware)) {
            array_push(self::$middleware, [
                self::$group_middleware, 
                $middleware
            ]);
        } else {
            array_push(self::$middleware, [
                self::$group_middleware, 
                "\\application\\" . app_name() . "\\middleware\\" . $middleware
            ]);
        }
        if (is_object($callback)) {
            array_push(self::$callbacks, $callback);
        } else {
            array_push(self::$callbacks, "\\application\\" . app_name() . "\\controller\\" . $callback);
        }
    }

    /**
     * Defines callback if route is not found
     */
    public static function error($callback)
    {
        self::$error_callback = $callback;
    }

    public static function haltOnMatch($flag = true)
    {
        self::$halts = $flag;
    }

    /**
     * Runs the callback for the given request
     */
    public static function dispatch()
    {
        $uri = parse_url(request()->server['request_uri'], PHP_URL_PATH);
        $method = request()->server['request_method'];
        
        $searches = array_keys(self::$patterns);
        $replaces = array_values(self::$patterns);
        
        $found_route = false;
        
        self::$routes = preg_replace('/\/+/', '/', self::$routes);
        
        // Check if route is defined without regex
        if (in_array($uri, self::$routes)) {
            $route_pos = array_keys(self::$routes, $uri);
            foreach ($route_pos as $route) {
                
                // Using an ANY option to match both GET and POST requests
                if (self::$methods[$route] == $method || self::$methods[$route] == 'ANY' || (!empty(self::$maps[$route]) && in_array($method, self::$maps[$route]))) {
                    $found_route = true;
                    foreach (self::$middleware[$route] as $middleware) {
                        if ($middleware != null) {
                            if (!is_object($middleware)) {
                                // Grab all parts based on a / separator
                                $parts_m = explode('/', $middleware);
                                
                                // Collect the last index of the array
                                $last_m = end($parts_m);
                                
                                // Grab the controller name and method call
                                $segments_m = explode('@', $last_m);
                                
                                // Instanitate controller
                                $controller_m = new $segments_m[0]();
                                
                                // Call method
                                $controller_m->{$segments_m[1]}();
                            } else {
                                // Call closure
                                call_user_func($middleware);
                            }
                        }
                    }
                    
                    // If route is not an object
                    if (!is_object(self::$callbacks[$route])) {
                        
                        // Grab all parts based on a / separator
                        $parts = explode('/', self::$callbacks[$route]);
                        
                        // Collect the last index of the array
                        $last = end($parts);
                        
                        // Grab the controller name and method call
                        $segments = explode('@', $last);
                        
                        // Instanitate controller
                        $controller = new $segments[0]();
                        
                        // Call method
                        return $controller->{$segments[1]}();
                        
                        if (self::$halts)
                            return;
                    } else {
                        // Call closure
                        return call_user_func(self::$callbacks[$route]);
                        
                        if (self::$halts)
                            return;
                    }
                }
            }
        } else {
            // Check if defined with regex
            $pos = 0;
            foreach (self::$routes as $route) {
                if (strpos($route, ':') !== false) {
                    $route = str_replace($searches, $replaces, $route);
                }
                
                if (preg_match('#^' . $route . '$#', $uri, $matched)) {
                    if (self::$methods[$pos] == $method || self::$methods[$pos] == 'ANY' || (!empty(self::$maps[$pos]) && in_array($method, self::$maps[$pos]))) {
                        $found_route = true;
                        
                        // Remove $matched[0] as [1] is the first parameter.
                        array_shift($matched);
                        foreach (self::$middleware[$pos] as $middleware) {
                            if ($middleware != null) {
                                if (!is_object($middleware)) {
                                    // Grab all parts based on a / separator
                                    $parts_m = explode('/', $middleware);
                                    
                                    // Collect the last index of the array
                                    $last_m = end($parts_m);
                                    
                                    // Grab the controller name and method call
                                    $segments_m = explode('@', $last_m);
                                    
                                    // Instanitate controller
                                    $controller_m = new $segments_m[0]();
                                    
                                    // Call method
                                    $controller_m->{$segments_m[1]}();
                                } else {
                                    // Call closure
                                    call_user_func($middleware);
                                }
                            }
                        }
                        
                        if (!is_object(self::$callbacks[$pos])) {
                            
                            // Grab all parts based on a / separator
                            $parts = explode('/', self::$callbacks[$pos]);
                            
                            // Collect the last index of the array
                            $last = end($parts);
                            
                            // Grab the controller name and method call
                            $segments = explode('@', $last);
                            
                            // Instanitate controller
                            $controller = new $segments[0]();
                            
                            // Fix multi parameters
                            if (!method_exists($controller, $segments[1])) {
                                throw new \Exception("controller and action not found");
                            } else {
                                return call_user_func_array(array(
                                    $controller, 
                                    $segments[1]
                                ), $matched);
                            }
                            
                            if (self::$halts)
                                return;
                        } else {
                            return call_user_func_array(self::$callbacks[$pos], $matched);
                            
                            if (self::$halts)
                                return;
                        }
                    }
                }
                $pos++;
            }
        }
        
        // Run the error callback if the route was not found
        if ($found_route == false) {
            if (!self::$error_callback) {
                self::$error_callback = function () {
                    throw new \Exception("404 Not Found");
                };
            } else {
                if (is_string(self::$error_callback)) {
                    self::get(request()->server['request_uri'], self::$error_callback);
                    self::$error_callback = null;
                    self::dispatch();
                    return;
                }
            }
            return call_user_func(self::$error_callback);
        }
    }
}
