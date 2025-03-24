<?php
/**
 * Modelo de Almacén
 * Gestiona todas las operaciones relacionadas con almacenes
 */
class warehouse {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Asegurar que BASEPATH está definido
        if (!defined('BASEPATH')) {
            define('BASEPATH', dirname(dirname(__FILE__)));
            define('CONFIGPATH', BASEPATH . '/config/');
        }
        
        // Incluir el archivo de base de datos con la clase Database
        require_once CONFIGPATH . 'database.php';
        
        // Inicializar la conexión a la base de datos
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtiene todos los almacenes
     * 
     * @param bool $includeInactive Incluir almacenes inactivos
     * @return array Almacenes
     */
    public function getAll($includeInactive = false) {
        try {
            $sql = "
                SELECT a.id_almacen, a.nombre, a.descripcion, a.direccion, a.codigo, 
                       a.es_principal, a.estado, a.fecha_creacion, a.fecha_actualizacion
                FROM almacen a
            ";
            
            if (!$includeInactive) {
                $sql .= " WHERE a.estado = 1";
            }
            
            $sql .= " ORDER BY a.es_principal DESC, a.nombre ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $warehouses = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $warehouses[] = [
                    'id' => $row['id_almacen'],
                    'nombre' => $row['nombre'],
                    'descripcion' => $row['descripcion'],
                    'direccion' => $row['direccion'],
                    'codigo' => $row['codigo'],
                    'es_principal' => $row['es_principal'],
                    'estado' => $row['estado'],
                    'fecha_creacion' => $row['fecha_creacion'],
                    'fecha_actualizacion' => $row['fecha_actualizacion']
                ];
            }
            
            return [
                'success' => true,
                'almacenes' => $warehouses
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getAll: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener almacenes: ' . $e->getMessage(),
                'almacenes' => []
            ];
        }
    }
    
    /**
     * Obtiene un almacén por su ID
     * 
     * @param int $id ID del almacén
     * @return array Datos del almacén
     */
    public function getById($id) {
        try {
            $sql = "
                SELECT a.id_almacen, a.nombre, a.descripcion, a.direccion, a.codigo, 
                       a.es_principal, a.estado, a.fecha_creacion, a.fecha_actualizacion
                FROM almacen a
                WHERE a.id_almacen = :id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Almacén no encontrado'
                ];
            }
            
            $warehouse = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener estadísticas de inventario para este almacén
            $statsSql = "
                SELECT 
                    COUNT(DISTINCT i.id_producto) as total_productos,
                    SUM(i.cantidad) as total_unidades,
                    (SELECT COUNT(*) FROM inventario WHERE id_almacen = :id AND cantidad = 0) as productos_sin_stock,
                    (SELECT COUNT(*) FROM inventario WHERE id_almacen = :id AND cantidad <= 5 AND cantidad > 0) as productos_stock_bajo
                FROM inventario i
                WHERE i.id_almacen = :id
            ";
            
            $statsStmt = $this->db->prepare($statsSql);
            $statsStmt->execute([':id' => $id]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'almacen' => [
                    'id' => $warehouse['id_almacen'],
                    'nombre' => $warehouse['nombre'],
                    'descripcion' => $warehouse['descripcion'],
                    'direccion' => $warehouse['direccion'],
                    'codigo' => $warehouse['codigo'],
                    'es_principal' => $warehouse['es_principal'],
                    'estado' => $warehouse['estado'],
                    'fecha_creacion' => $warehouse['fecha_creacion'],
                    'fecha_actualizacion' => $warehouse['fecha_actualizacion'],
                    'estadisticas' => [
                        'total_productos' => (int)$stats['total_productos'],
                        'total_unidades' => (int)$stats['total_unidades'],
                        'productos_sin_stock' => (int)$stats['productos_sin_stock'],
                        'productos_stock_bajo' => (int)$stats['productos_stock_bajo']
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getById: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener el almacén: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crea un nuevo almacén
     * 
     * @param array $warehouseData Datos del almacén
     * @return array Resultado de la operación
     */
    public function create($warehouseData) {
        try {
            // Validar datos
            if (empty($warehouseData['nombre'])) {
                return [
                    'success' => false,
                    'message' => 'El nombre del almacén es requerido'
                ];
            }
            
            // Generar código si no se proporciona
            if (empty($warehouseData['codigo'])) {
                $warehouseData['codigo'] = $this->generateCode($warehouseData['nombre']);
            }
            
            // Verificar que el código sea único
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM almacen WHERE codigo = :codigo");
            $checkStmt->execute([':codigo' => $warehouseData['codigo']]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'Ya existe un almacén con ese código'
                ];
            }
            
            // Si se marca como principal, desmarcar los demás
            if (isset($warehouseData['es_principal']) && $warehouseData['es_principal']) {
                $this->db->exec("UPDATE almacen SET es_principal = 0");
            }
            
            // Insertar almacén
            $stmt = $this->db->prepare("
                INSERT INTO almacen (
                    nombre, descripcion, direccion, codigo, 
                    es_principal, estado, fecha_creacion, fecha_actualizacion
                ) VALUES (
                    :nombre, :descripcion, :direccion, :codigo, 
                    :es_principal, :estado, NOW(), NOW()
                )
            ");
            
            $stmt->execute([
                ':nombre' => $warehouseData['nombre'],
                ':descripcion' => $warehouseData['descripcion'] ?? '',
                ':direccion' => $warehouseData['direccion'] ?? '',
                ':codigo' => $warehouseData['codigo'],
                ':es_principal' => $warehouseData['es_principal'] ?? 0,
                ':estado' => $warehouseData['estado'] ?? 1
            ]);
            
            $warehouseId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Almacén creado correctamente',
                'id' => $warehouseId
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en create: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al crear el almacén: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualiza un almacén existente
     * 
     * @param int $id ID del almacén
     * @param array $warehouseData Datos del almacén
     * @return array Resultado de la operación
     */
    public function update($id, $warehouseData) {
        try {
            // Verificar si el almacén existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM almacen WHERE id_almacen = :id");
            $checkStmt->execute([':id' => $id]);
            
            if ($checkStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Almacén no encontrado'
                ];
            }
            
            // Si se actualiza el código, verificar que sea único
            if (isset($warehouseData['codigo'])) {
                $checkCodeStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM almacen 
                    WHERE codigo = :codigo AND id_almacen != :id
                ");
                
                $checkCodeStmt->execute([
                    ':codigo' => $warehouseData['codigo'],
                    ':id' => $id
                ]);
                
                if ($checkCodeStmt->fetchColumn() > 0) {
                    return [
                        'success' => false,
                        'message' => 'Ya existe otro almacén con ese código'
                    ];
                }
            }
            
            // Si se marca como principal, desmarcar los demás
            if (isset($warehouseData['es_principal']) && $warehouseData['es_principal']) {
                $this->db->exec("UPDATE almacen SET es_principal = 0");
            }
            
            // Preparar campos a actualizar
            $updateFields = [];
            $params = [':id' => $id];
            
            $possibleFields = [
                'nombre', 'descripcion', 'direccion', 'codigo', 
                'es_principal', 'estado'
            ];
            
            foreach ($possibleFields as $field) {
                if (isset($warehouseData[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[":$field"] = $warehouseData[$field];
                }
            }
            
            // Agregar fecha de actualización
            $updateFields[] = "fecha_actualizacion = NOW()";
            
            // Si hay campos para actualizar
            if (!empty($updateFields)) {
                $sql = "UPDATE almacen SET " . implode(', ', $updateFields) . " WHERE id_almacen = :id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
            
            return [
                'success' => true,
                'message' => 'Almacén actualizado correctamente'
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en update: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al actualizar el almacén: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Elimina un almacén
     * 
     * @param int $id ID del almacén
     * @return array Resultado de la operación
     */
    public function delete($id) {
        try {
            // Verificar si el almacén existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*), es_principal FROM almacen WHERE id_almacen = :id");
            $checkStmt->execute([':id' => $id]);
            
            $result = $checkStmt->fetch(PDO::FETCH_NUM);
            if ($result[0] == 0) {
                return [
                    'success' => false,
                    'message' => 'Almacén no encontrado'
                ];
            }
            
            // No permitir eliminar el almacén principal
            if ($result[1] == 1) {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar el almacén principal'
                ];
            }
            
            // Verificar si hay inventario en este almacén
            $inventoryCheckStmt = $this->db->prepare("
                SELECT COUNT(*) FROM inventario WHERE id_almacen = :id AND cantidad > 0
            ");
            $inventoryCheckStmt->execute([':id' => $id]);
            
            if ($inventoryCheckStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar el almacén porque tiene productos en inventario'
                ];
            }
            
            // Eliminar registros de inventario vacíos
            $deleteInventoryStmt = $this->db->prepare("
                DELETE FROM inventario WHERE id_almacen = :id AND cantidad = 0
            ");
            $deleteInventoryStmt->execute([':id' => $id]);
            
            // Eliminar el almacén
            $deleteStmt = $this->db->prepare("DELETE FROM almacen WHERE id_almacen = :id");
            $deleteStmt->execute([':id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Almacén eliminado correctamente'
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en delete: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al eliminar el almacén: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cambia el estado de un almacén (activar/desactivar)
     * 
     * @param int $id ID del almacén
     * @param int $estado Nuevo estado (1=activo, 0=inactivo)
     * @return array Resultado de la operación
     */
    public function changeState($id, $estado) {
        try {
            // Verificar si es el almacén principal
            $checkStmt = $this->db->prepare("SELECT es_principal FROM almacen WHERE id_almacen = :id");
            $checkStmt->execute([':id' => $id]);
            
            if ($checkStmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Almacén no encontrado'
                ];
            }
            
            $esPrincipal = $checkStmt->fetchColumn();
            
            // No permitir desactivar el almacén principal
            if ($esPrincipal && $estado == 0) {
                return [
                    'success' => false,
                    'message' => 'No se puede desactivar el almacén principal'
                ];
            }
            
            $stmt = $this->db->prepare("
                UPDATE almacen 
                SET estado = :estado, fecha_actualizacion = NOW()
                WHERE id_almacen = :id
            ");
            
            $stmt->execute([
                ':id' => $id,
                ':estado' => $estado ? 1 : 0
            ]);
            
            return [
                'success' => true,
                'message' => 'Estado del almacén actualizado correctamente'
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en changeState: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado del almacén: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Establece un almacén como principal
     * 
     * @param int $id ID del almacén
     * @return array Resultado de la operación
     */
    public function setAsPrimary($id) {
        try {
            // Verificar si el almacén existe y está activo
            $checkStmt = $this->db->prepare("
                SELECT COUNT(*), estado FROM almacen WHERE id_almacen = :id
            ");
            $checkStmt->execute([':id' => $id]);
            
            $result = $checkStmt->fetch(PDO::FETCH_NUM);
            if ($result[0] == 0) {
                return [
                    'success' => false,
                    'message' => 'Almacén no encontrado'
                ];
            }
            
            // Verificar que el almacén esté activo
            if ($result[1] != 1) {
                return [
                    'success' => false,
                    'message' => 'No se puede establecer como principal un almacén inactivo'
                ];
            }
            
            // Quitar el estado principal de todos los almacenes
            $this->db->exec("UPDATE almacen SET es_principal = 0, fecha_actualizacion = NOW()");
            
            // Establecer el nuevo almacén principal
            $stmt = $this->db->prepare("
                UPDATE almacen 
                SET es_principal = 1, fecha_actualizacion = NOW()
                WHERE id_almacen = :id
            ");
            
            $stmt->execute([':id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Almacén establecido como principal correctamente'
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en setAsPrimary: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al establecer almacén como principal: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene el inventario de un almacén
     * 
     * @param int $warehouseId ID del almacén
     * @param array $filters Filtros opcionales
     * @param int $limit Límite de resultados
     * @param int $offset Desplazamiento
     * @return array Inventario del almacén
     */
    public function getInventory($warehouseId, $filters = [], $limit = 50, $offset = 0) {
        try {
            // Verificar si el almacén existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM almacen WHERE id_almacen = :id");
            $checkStmt->execute([':id' => $warehouseId]);
            
            if ($checkStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Almacén no encontrado',
                    'inventario' => []
                ];
            }
            
            // Construir consulta base
            $sql = "
                SELECT i.id_inventario, i.id_producto, i.cantidad, i.fecha_actualizacion,
                       p.codigo as producto_codigo, p.nombre as producto_nombre, p.precio_base,
                       c.id_categoria, c.nombre as categoria_nombre,
                       (SELECT ruta FROM producto_imagen WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as imagen
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                WHERE i.id_almacen = :id_almacen
            ";
            
            $params = [':id_almacen' => $warehouseId];
            
            // Aplicar filtros adicionales
            if (!empty($filters)) {
                // Filtro por categoría
                if (isset($filters['id_categoria'])) {
                    $sql .= " AND p.id_categoria = :id_categoria";
                    $params[':id_categoria'] = $filters['id_categoria'];
                }
                
                // Filtro por nombre o código de producto
                if (isset($filters['busqueda'])) {
                    $sql .= " AND (p.nombre LIKE :busqueda OR p.codigo LIKE :busqueda)";
                    $params[':busqueda'] = '%' . $filters['busqueda'] . '%';
                }
                
                // Filtro por stock bajo
                if (isset($filters['stock_bajo']) && $filters['stock_bajo']) {
                    $sql .= " AND i.cantidad <= :stock_bajo_umbral AND i.cantidad > 0";
                    $params[':stock_bajo_umbral'] = $filters['stock_bajo_umbral'] ?? 5;
                }
                
                // Filtro por sin stock
                if (isset($filters['sin_stock']) && $filters['sin_stock']) {
                    $sql .= " AND i.cantidad = 0";
                }
                
                // Filtro por con stock
                if (isset($filters['con_stock']) && $filters['con_stock']) {
                    $sql .= " AND i.cantidad > 0";
                }
            }
            
            // Ordenamiento
            if (isset($filters['orden'])) {
                switch ($filters['orden']) {
                    case 'nombre_asc':
                        $sql .= " ORDER BY p.nombre ASC";
                        break;
                    case 'nombre_desc':
                        $sql .= " ORDER BY p.nombre DESC";
                        break;
                    case 'stock_asc':
                        $sql .= " ORDER BY i.cantidad ASC";
                        break;
                    case 'stock_desc':
                        $sql .= " ORDER BY i.cantidad DESC";
                        break;
                    case 'categoria':
                        $sql .= " ORDER BY c.nombre ASC, p.nombre ASC";
                        break;
                    default:
                        $sql .= " ORDER BY p.nombre ASC";
                }
            } else {
                // Orden predeterminado
                $sql .= " ORDER BY p.nombre ASC";
            }
            
            // Paginación
            $sql .= " LIMIT :limit OFFSET :offset";
            
            // Consulta para contar el total
            $countSql = "
                SELECT COUNT(*) 
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                WHERE i.id_almacen = :id_almacen
            ";
            
            // Aplicar los mismos filtros a la consulta de conteo
            if (!empty($filters)) {
                if (isset($filters['id_categoria'])) {
                    $countSql .= " AND p.id_categoria = :id_categoria";
                }
                
                if (isset($filters['busqueda'])) {
                    $countSql .= " AND (p.nombre LIKE :busqueda OR p.codigo LIKE :busqueda)";
                }
                
                if (isset($filters['stock_bajo']) && $filters['stock_bajo']) {
                    $countSql .= " AND i.cantidad <= :stock_bajo_umbral AND i.cantidad > 0";
                }
                
                if (isset($filters['sin_stock']) && $filters['sin_stock']) {
                    $countSql .= " AND i.cantidad = 0";
                }
                
                if (isset($filters['con_stock']) && $filters['con_stock']) {
                    $countSql .= " AND i.cantidad > 0";
                }
            }
            
            // Ejecutar consulta de conteo
            $countStmt = $this->db->prepare($countSql);
            
            // Bindear parámetros para el conteo
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            
            $countStmt->execute();
            $totalItems = $countStmt->fetchColumn();
            
            // Ejecutar consulta principal
            $stmt = $this->db->prepare($sql);
            
            // Bindear parámetros de limit y offset
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            // Bindear los demás parámetros
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            // Construir resultado
            $items = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $items[] = [
                    'id' => $row['id_inventario'],
                    'producto' => [
                        'id' => $row['id_producto'],
                        'codigo' => $row['producto_codigo'],
                        'nombre' => $row['producto_nombre'],
                        'precio_base' => $row['precio_base'],
                        'imagen' => $row['imagen']
                    ],
                    'categoria' => [
                        'id' => $row['id_categoria'],
                        'nombre' => $row['categoria_nombre']
                    ],
                    'cantidad' => $row['cantidad'],
                    'fecha_actualizacion' => $row['fecha_actualizacion']
                ];
            }
            
            return [
                'success' => true,
                'inventario' => $items,
                'total' => $totalItems,
                'paginas' => ceil($totalItems / $limit)
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getInventory: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener el inventario del almacén: ' . $e->getMessage(),
                'inventario' => []
            ];
        }
    }
    
    /**
     * Genera un código único para el almacén
     * 
     * @param string $name Nombre del almacén
     * @return string Código generado
     */
    private function generateCode($name) {
        // Tomar las primeras letras del nombre y convertir a mayúsculas
        $code = '';
        $words = explode(' ', $name);
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $code .= strtoupper(substr($word, 0, 1));
            }
        }
        
        // Si el código resultante es muy corto, agregar más caracteres
        if (strlen($code) < 2) {
            $code = strtoupper(substr($name, 0, 3));
        }
        
        // Agregar un sufijo numérico
        $code .= '-' . date('Ym');
        
        // Verificar si el código ya existe y agregar un sufijo incremental si es necesario
        $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM almacen WHERE codigo LIKE :codigo");
        $checkStmt->execute([':codigo' => $code . '%']);
        
        $count = $checkStmt->fetchColumn();
        
        if ($count > 0) {
            $code .= '-' . ($count + 1);
        }
        
        return $code;
    }
    
    /**
     * Registra un error en el log
     * 
     * @param string $message Mensaje de error
     * @return void
     */
    private function logError($message) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/warehouse_model_' . date('Y-m-d') . '.log';
        $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}