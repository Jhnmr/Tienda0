<?php
/**
 * Router - Maneja el enrutamiento de la aplicación
 */
class Router {
    private $routes = [];
    private $notFoundCallback;
    
    /**
     * Añade una ruta al enrutador
     * 
     * @param string $method Método HTTP (GET, POST, etc)
     * @param string $route Patrón de ruta
     * @param mixed $handler Controlador@método o función callback
     * @param array $middlewares Arreglo de middlewares a aplicar
     */
    public function add($method, $route, $handler, $middlewares = []) {
        $this->routes[] = [
            'method' => $method,
            'route' => $route,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }
    
    /**
     * Añade una ruta GET
     */
    public function get($route, $handler, $middlewares = []) {
        $this->add('GET', $route, $handler, $middlewares);
    }
    
    /**
     * Añade una ruta POST
     */
    public function post($route, $handler, $middlewares = []) {
        $this->add('POST', $route, $handler, $middlewares);
    }
    
    /**
     * Establece el callback para rutas no encontradas
     */
    public function notFound($callback) {
        $this->notFoundCallback = $callback;
    }
    
    /**
     * Ejecuta el middleware especificado
     */
    private function runMiddleware($middleware, $params = []) {
        if (is_string($middleware)) {
            // Si es un string, asumimos que es una función de middleware
            if (function_exists($middleware)) {
                call_user_func_array($middleware, $params);
            }
        } elseif (is_callable($middleware)) {
            // Si es una función anónima
            call_user_func_array($middleware, $params);
        }
    }
    
    /**
     * Ejecuta el handler especificado
     */
    private function executeHandler($handler, $params = []) {
        if (is_string($handler)) {
            // Si es un string, asumimos formato "Controller@method"
            list($controller, $method) = explode('@', $handler);
            $controllerFile = "controllers/{$controller}.php";
            
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                $controllerInstance = new $controller();
                return call_user_func_array([$controllerInstance, $method], $params);
            }
        } elseif (is_callable($handler)) {
            // Si es una función anónima
            return call_user_func_array($handler, $params);
        }
        
        throw new Exception("Handler not valid or controller file not found");
    }
    
    /**
     * Despacha la solicitud a la ruta correspondiente
     */
    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = $_SERVER['REQUEST_URI'];
        
        // Eliminar query string si existe
        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }
        
        // Eliminar la base del path si la aplicación no está en la raíz
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/' && $basePath !== '\\') {
            $requestUri = substr($requestUri, strlen($basePath));
        }
        
        // Normalizar la ruta
        $requestUri = trim($requestUri, '/');
        if (empty($requestUri)) {
            $requestUri = '/';
        } else {
            $requestUri = '/' . $requestUri;
        }
        
        // Buscar coincidencia en las rutas
        foreach ($this->routes as $route) {
            // Verificar método HTTP
            if ($route['method'] !== $requestMethod && $route['method'] !== 'ANY') {
                continue;
            }
            
            $pattern = $this->buildRoutePattern($route['route']);
            if (preg_match($pattern, $requestUri, $matches)) {
                array_shift($matches); // Eliminar coincidencia completa
                
                // Ejecutar middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $this->runMiddleware($middleware);
                }
                
                // Ejecutar el handler
                return $this->executeHandler($route['handler'], $matches);
            }
        }
        
        // Si llegamos aquí, no se encontró la ruta
        if ($this->notFoundCallback) {
            return call_user_func($this->notFoundCallback);
        } else {
            header("HTTP/1.0 404 Not Found");
            echo "404 - Page Not Found";
        }
    }
    
    /**
     * Convierte una ruta en un patrón de expresión regular
     */
    private function buildRoutePattern($route) {
        // Convertir parámetros de ruta (:param) en grupos de captura de regex
        $routePattern = preg_replace('/\/:([^\/]+)/', '/(?<$1>[^/]+)', $route);
        
        // Escapar slashes y añadir delimitadores
        $routePattern = '#^' . str_replace('/', '\/', $routePattern) . '$#';
        
        return $routePattern;
    }
}