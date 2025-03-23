<?php
/**
 * Sistema de Enrutamiento
 * Gestiona las rutas y dirige las peticiones a los controladores correctos
 */

// Prevenir acceso directo al archivo
if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

class router {
    /**
     * Rutas registradas
     * @var array
     */
    private $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => []
    ];
    
    /**
     * Patrones de rutas
     * @var array
     */
    private $patterns = [
        ':any' => '(.+)',
        ':num' => '([0-9]+)',
        ':alpha' => '([a-zA-Z]+)',
        ':alphanum' => '([a-zA-Z0-9]+)',
        ':slug' => '([a-z0-9-]+)'
    ];
    
    /**
     * Rutas para grupos
     * @var string
     */
    private $groupPrefix = '';
    
    /**
     * Middleware para grupos
     * @var array
     */
    private $groupMiddleware = [];
    
    /**
     * Configuración de rutas
     * @var array
     */
    private $config;
    
    /**
     * Constructor
     * @param array $config Configuración de rutas desde config.php
     */
    public function __construct($config) {
        $this->config = $config;
    }
    
    /**
     * Registra una ruta GET
     * @param string $uri URI a registrar
     * @param string|callable $handler Controlador@método o función anónima
     * @param array $middleware Middleware a aplicar
     * @return $this
     */
    public function get($uri, $handler, $middleware = []) {
        return $this->addRoute('GET', $uri, $handler, $middleware);
    }
    
    /**
     * Registra una ruta POST
     * @param string $uri URI a registrar
     * @param string|callable $handler Controlador@método o función anónima
     * @param array $middleware Middleware a aplicar
     * @return $this
     */
    public function post($uri, $handler, $middleware = []) {
        return $this->addRoute('POST', $uri, $handler, $middleware);
    }
    
    /**
     * Registra una ruta PUT
     * @param string $uri URI a registrar
     * @param string|callable $handler Controlador@método o función anónima
     * @param array $middleware Middleware a aplicar
     * @return $this
     */
    public function put($uri, $handler, $middleware = []) {
        return $this->addRoute('PUT', $uri, $handler, $middleware);
    }
    
    /**
     * Registra una ruta DELETE
     * @param string $uri URI a registrar
     * @param string|callable $handler Controlador@método o función anónima
     * @param array $middleware Middleware a aplicar
     * @return $this
     */
    public function delete($uri, $handler, $middleware = []) {
        return $this->addRoute('DELETE', $uri, $handler, $middleware);
    }
    
    /**
     * Registra rutas de recursos (CRUD)
     * @param string $name Nombre del recurso
     * @param string $controller Controlador a usar
     * @param array $middleware Middleware a aplicar
     * @return $this
     */
    public function resource($name, $controller, $middleware = []) {
        $this->get("{$name}", "{$controller}@index", $middleware);
        $this->get("{$name}/create", "{$controller}@create", $middleware);
        $this->post("{$name}", "{$controller}@store", $middleware);
        $this->get("{$name}/:num", "{$controller}@show", $middleware);
        $this->get("{$name}/:num/edit", "{$controller}@edit", $middleware);
        $this->put("{$name}/:num", "{$controller}@update", $middleware);
        $this->delete("{$name}/:num", "{$controller}@destroy", $middleware);
        
        return $this;
    }
    
    /**
     * Agrupa rutas con un prefijo común
     * @param string $prefix Prefijo de ruta
     * @param callable $callback Función para definir rutas en el grupo
     * @param array $middleware Middleware del grupo
     * @return $this
     */
    public function group($prefix, $callback, $middleware = []) {
        $previousGroupPrefix = $this->groupPrefix;
        $previousGroupMiddleware = $this->groupMiddleware;
        
        $this->groupPrefix = $previousGroupPrefix . '/' . trim($prefix, '/');
        $this->groupMiddleware = array_merge($previousGroupMiddleware, $middleware);
        
        call_user_func($callback, $this);
        
        $this->groupPrefix = $previousGroupPrefix;
        $this->groupMiddleware = $previousGroupMiddleware;
        
        return $this;
    }
    
    /**
     * Agrega una ruta al router
     * @param string $method Método HTTP
     * @param string $uri URI de la ruta
     * @param string|callable $handler Controlador@método o función anónima
     * @param array $middleware Middleware a aplicar
     * @return $this
     */
    private function addRoute($method, $uri, $handler, $middleware = []) {
        // Preparar la URI con el prefijo del grupo
        $uri = $this->groupPrefix . '/' . trim($uri, '/');
        $uri = trim($uri, '/');
        
        // Si la URI está vacía, usar '/'
        if (empty($uri)) {
            $uri = '/';
        }
        
        // Convertir los patrones de URI
        $uri = $this->convertPatterns($uri);
        
        // Registrar la ruta
        $this->routes[$method][$uri] = [
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware)
        ];
        
        return $this;
    }
    
    /**
     * Convierte los patrones de URI a expresiones regulares
     * @param string $uri URI a convertir
     * @return string
     */
    private function convertPatterns($uri) {
        // Reemplazar patrones como :any, :num, etc. con expresiones regulares
        foreach ($this->patterns as $pattern => $replacement) {
            $uri = str_replace($pattern, $replacement, $uri);
        }
        
        return $uri;
    }
    
    /**
     * Resuelve la ruta actual y ejecuta el controlador correspondiente
     * @param string $uri URI actual
     * @param string $method Método HTTP actual
     * @return mixed
     */
    public function resolve($uri, $method) {
        // Normalizar la URI
        $uri = trim($uri, '/');
        if (empty($uri)) {
            $uri = '/';
        }
        
        // Si es una ruta exacta
        if (isset($this->routes[$method][$uri])) {
            return $this->handleRoute($this->routes[$method][$uri], []);
        }
        
        // Si no es una ruta exacta, buscar coincidencias con patrones
        foreach ($this->routes[$method] as $route => $routeData) {
            if ($route === '/') {
                continue; // Ya comprobamos esta ruta
            }
            
            // Si la ruta tiene patrones, usar expresiones regulares para comprobar coincidencia
            if (strpos($route, '(') !== false) {
                $pattern = "#^{$route}$#";
                if (preg_match($pattern, $uri, $matches)) {
                    array_shift($matches); // Eliminar la coincidencia completa
                    return $this->handleRoute($routeData, $matches);
                }
            }
        }
        
        // Si no hay coincidencias, intentar con la ruta por defecto
        if ($uri === '/' || empty($uri)) {
            return $this->handleDefaultRoute();
        }
        
        // Si aún no hay coincidencias, mostrar error 404
        return $this->handleNotFound();
    }
    
    /**
     * Ejecuta el controlador para la ruta encontrada
     * @param array $routeData Datos de la ruta
     * @param array $params Parámetros de la ruta
     * @return mixed
     */
    private function handleRoute($routeData, $params) {
        $handler = $routeData['handler'];
        $middleware = $routeData['middleware'];
        
        // Aplicar middleware
        foreach ($middleware as $m) {
            // Permitir middleware en formato string 'NombreMiddleware' o array ['NombreMiddleware', $params]
            $middlewareClass = is_array($m) ? $m[0] : $m;
            $middlewareParams = is_array($m) && isset($m[1]) ? $m[1] : [];
            
            $middlewareFile = MIDDLEWAREPATH . $middlewareClass . '.php';
            
            if (file_exists($middlewareFile)) {
                require_once $middlewareFile;
                
                $middlewareInstance = new $middlewareClass();
                $result = $middlewareInstance->handle($middlewareParams);
                
                // Si el middleware retorna algo, detener la ejecución
                if ($result !== null) {
                    return $result;
                }
            }
        }
        
        // Si el handler es una función anónima
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        
        // Si el handler es un string "Controlador@metodo"
        if (is_string($handler)) {
            list($controller, $method) = explode('@', $handler);
            
            // Verificar si se especificó un namespace o si se trata de un controlador de área
            if (strpos($controller, '\\') === false) {
                // Si contiene "admin/" o "shop/", es un controlador de área
                if (strpos($controller, 'admin/') === 0) {
                    $controller = str_replace('admin/', '', $controller);
                    $controllerFile = CONTROLLERSPATH . 'admin/' . $controller . '.php';
                    $controllerClass = $controller;
                } else if (strpos($controller, 'shop/') === 0) {
                    $controller = str_replace('shop/', '', $controller);
                    $controllerFile = CONTROLLERSPATH . 'shop/' . $controller . '.php';
                    $controllerClass = $controller;
                } else {
                    // Controlador principal
                    $controllerFile = CONTROLLERSPATH . $controller . '.php';
                    $controllerClass = $controller;
                }
            } else {
                // Controlador con namespace, resolver la ruta basada en el namespace
                $namespaceController = str_replace('\\', DIRECTORY_SEPARATOR, $controller);
                $controllerFile = APPPATH . $namespaceController . '.php';
                $controllerClass = $controller;
            }
            
            // Verificar si el archivo del controlador existe
            if (!file_exists($controllerFile)) {
                return $this->handleNotFound();
            }
            
            // Cargar el controlador
            require_once $controllerFile;
            
            // Crear instancia del controlador
            $controllerInstance = new $controllerClass();
            
            // Verificar si el método existe
            if (!method_exists($controllerInstance, $method)) {
                return $this->handleNotFound();
            }
            
            // Ejecutar el método con los parámetros
            return call_user_func_array([$controllerInstance, $method], $params);
        }
        
        return $this->handleNotFound();
    }
    
    /**
     * Maneja la ruta por defecto
     * @return mixed
     */
    private function handleDefaultRoute() {
        $defaultController = $this->config['routes']['default_controller'];
        $defaultMethod = $this->config['routes']['default_method'];
        
        return $this->handleRoute([
            'handler' => "{$defaultController}@{$defaultMethod}",
            'middleware' => []
        ], []);
    }
    
    /**
     * Maneja una ruta no encontrada (404)
     * @return mixed
     */
    private function handleNotFound() {
        $errorController = $this->config['routes']['error_controller'];
        
        return $this->handleRoute([
            'handler' => "{$errorController}@notFound",
            'middleware' => []
        ], []);
    }
}