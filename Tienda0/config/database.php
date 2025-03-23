<?php
/**
 * Configuración de la base de datos
 */
 
// Prevenir acceso directo al archivo
if (!defined('BASEPATH')) {
    exit('No se permite el acceso directo al script');
}

$database = [
    // Entorno de desarrollo
    'development' => [
        'driver'    => 'mysql',
        'host'      => 'localhost',
        'database'  => 'tienda0',
        'username'  => 'root',
        'password'  => 'admin',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'port'      => '3306',
        'prefix'    => '', 
        'strict'    => true,
        'options'   => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    ],

    // Entorno de prueba
    'testing' => [
        'driver'    => 'mysql',
        'host'      => 'localhost',
        'database'  => 'tienda_test',
        'username'  => 'test_user',
        'password'  => 'test_password',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'port'      => '3306',
        'prefix'    => '', 
        'strict'    => true,
        'options'   => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    ],

    // Entorno de producción
    'production' => [
        'driver'    => 'mysql',
        'host'      => 'localhost',
        'database'  => 'tienda_prod',
        'username'  => 'prod_user',
        'password'  => 'prod_password', // En producción usar variables de entorno
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'port'      => '3306',
        'prefix'    => '', 
        'strict'    => true,
        'options'   => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    ]
];
/**
 * Clase Singleton para manejar la conexión a la base de datos
 */
class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct() {
        global $database;
        
        // Cargar configuración
        $config = require CONFIGPATH . 'config.php';
        $environment = $config['environment'];
        
        // Obtener configuración para el entorno actual
        $dbConfig = $database[$environment];
        
        // Crear DSN para la conexión
        $dsn = "{$dbConfig['driver']}:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
        
        try {
            // Crear conexión PDO
            $this->connection = new PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['options']
            );
        } catch (PDOException $e) {
            // En desarrollo, mostrar error
            if ($environment === 'development') {
                die("Error de conexión a la base de datos: " . $e->getMessage());
            } else {
                // En producción, registrar error y mostrar mensaje genérico
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
     * Evita que se pueda clonar el objeto
     */
    private function __clone() {}
    
    /**
     * Evita que se pueda deserializar el objeto
     */
    public function __wakeup() {
        throw new Exception("No se puede deserializar una instancia de Database");
    }
}

// Devolver la configuración
return $database;