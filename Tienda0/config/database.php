<?php
/**
 * Configuración de la base de datos
 */
class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct() {
        $config = require_once __DIR__ . '/config.php';
        $db_config = $config['database'];
        
        $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
        
        try {
            $this->connection = new PDO($dsn, $db_config['username'], $db_config['password'], $db_config['options']);
        } catch (PDOException $e) {
            // En producción, nunca mostrar detalles del error, solo registrarlos
            if (isset($config['app']['debug']) && $config['app']['debug']) {
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                die("Error de conexión a la base de datos: " . $e->getMessage());
            } else {
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                die("Ha ocurrido un error al conectar con la base de datos. Por favor, contacte al administrador.");
            }
        }
    }
    
    /**
     * Obtiene la instancia única de la base de datos (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtiene la conexión PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prevenir clonación del objeto
     */
    private function __clone() {}
    
    /**
     * Prevenir deserialización del objeto
     */
    public function __wakeup() {
        throw new Exception("No se puede deserializar un singleton");
    }
}