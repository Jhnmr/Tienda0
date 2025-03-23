<?php
/**
 * Sistema de enrutamiento
 */
class router {
    private $routes = [];
    
    /**
     * Agrega una ruta
     * 
     * @param string $route Patrón de ruta
     * @param string $handler Controlador@método
     */
    public function add($route, $handler) {
        $this->routes[$route] = $handler;
    }
    
    /**
     * Despacha la ruta solicitada
     * 
     * @param string $uri URI solicitada
     */
    public function dispatch($uri) {
        // Si la URI está vacía, usar la ruta por defecto
        if ($uri === '') {
            $uri = '';
        }
        
        // Buscar coincidencia exacta primero
        if (isset($this->routes[$uri])) {
            $this->executeHandler($this->routes[$uri]);
            return;
        }
        
        // Buscar coincidencia con patrones
        foreach ($this->routes as $route => $handler) {
            $pattern = '#^' . $route . '$#';
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Eliminar la coincidencia completa
                $this->executeHandler($handler, $matches);
                return;
            }
        }
        
        // No se encontró ruta
        $this->notFound();
    }
    
    /**
     * Ejecuta el controlador
     * 
     * @param string $handler Controlador@método
     * @param array $params Parámetros
     */
    private function executeHandler($handler, $params = []) {
        // Verificar si el handler tiene formato Controller@method
        if (strpos($handler, '@') === false) {
            throw new Exception("Handler de ruta inválido: $handler");
        }
        
        list($controller, $method) = explode('@', $handler);
        
        // Incluir el controlador
        $controllerFile = __DIR__ . '/../controllers/' . $controller . '.php';
        if (!file_exists($controllerFile)) {
            throw new Exception("Controlador no encontrado: $controller");
        }
        
        require_once $controllerFile;
        
        // Verificar si la clase del controlador existe
        if (!class_exists($controller)) {
            throw new Exception("Clase del controlador no encontrada: $controller");
        }
        
        // Instanciar controlador
        $controllerInstance = new $controller();
        
        // Verificar si el método existe
        if (!method_exists($controllerInstance, $method)) {
            throw new Exception("Método no encontrado: $controller::$method");
        }
        
        // Llamar al método con los parámetros
        call_user_func_array([$controllerInstance, $method], $params);
    }
    
    /**
     * Maneja la página no encontrada
     */
    private function notFound() {
        header("HTTP/1.0 404 Not Found");
        
        if (file_exists(__DIR__ . '/../views/errors/404.php')) {
            include __DIR__ . '/../views/errors/404.php';
        } else {
            echo "<h1>404 - Página no encontrada</h1>";
            echo "<p>La página que estás buscando no existe.</p>";
        }
        
        exit;
    }
}