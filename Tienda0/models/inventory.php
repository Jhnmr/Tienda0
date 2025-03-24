<?php
/**
 * Modelo de Inventario
 * Gestiona todas las operaciones relacionadas con el inventario de productos
 */
class inventory {
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
     * Obtiene el stock actual de un producto
     * 
     * @param int $productId ID del producto
     * @param int|null $warehouseId ID del almacén (opcional)
     * @return array Información de stock
     */
    public function getStock($productId, $warehouseId = null) {
        try {
            // Verificar que el producto existe
            $productCheckStmt = $this->db->prepare("SELECT COUNT(*) FROM producto WHERE id_producto = :id");
            $productCheckStmt->execute([':id' => $productId]);
            
            if ($productCheckStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado',
                    'stock' => 0
                ];
            }
            
            // Construir consulta base
            $sql = "
                SELECT i.id_inventario, i.id_producto, i.id_almacen, i.cantidad, i.fecha_actualizacion,
                       a.nombre as almacen_nombre
                FROM inventario i
                JOIN almacen a ON i.id_almacen = a.id_almacen
                WHERE i.id_producto = :id_producto
            ";
            
            $params = [':id_producto' => $productId];
            
            // Filtrar por almacén si se especifica
            if ($warehouseId !== null) {
                $sql .= " AND i.id_almacen = :id_almacen";
                $params[':id_almacen'] = $warehouseId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $stockItems = [];
            $totalStock = 0;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stockItems[] = [
                    'id' => $row['id_inventario'],
                    'id_producto' => $row['id_producto'],
                    'id_almacen' => $row['id_almacen'],
                    'almacen_nombre' => $row['almacen_nombre'],
                    'cantidad' => $row['cantidad'],
                    'fecha_actualizacion' => $row['fecha_actualizacion']
                ];
                
                $totalStock += $row['cantidad'];
            }
            
            return [
                'success' => true,
                'stock_items' => $stockItems,
                'stock_total' => $totalStock
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getStock: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener stock: ' . $e->getMessage(),
                'stock' => 0
            ];
        }
    }
    
    /**
     * Obtiene el inventario completo
     * 
     * @param array $filters Filtros opcionales
     * @param int $limit Límite de resultados
     * @param int $offset Desplazamiento
     * @return array Información de inventario
     */
    public function getAll($filters = [], $limit = 50, $offset = 0) {
        try {
            // Construir consulta base
            $sql = "
                SELECT i.id_inventario, i.id_producto, i.id_almacen, i.cantidad, i.fecha_actualizacion,
                       p.codigo as producto_codigo, p.nombre as producto_nombre,
                       a.nombre as almacen_nombre
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                JOIN almacen a ON i.id_almacen = a.id_almacen
            ";
            
            $where = [];
            $params = [];
            
            // Aplicar filtros
            if (!empty($filters)) {
                // Filtro por almacén
                if (isset($filters['id_almacen'])) {
                    $where[] = "i.id_almacen = :id_almacen";
                    $params[':id_almacen'] = $filters['id_almacen'];
                }
                
                // Filtro por producto
                if (isset($filters['id_producto'])) {
                    $where[] = "i.id_producto = :id_producto";
                    $params[':id_producto'] = $filters['id_producto'];
                }
                
                // Filtro por código de producto
                if (isset($filters['codigo'])) {
                    $where[] = "p.codigo LIKE :codigo";
                    $params[':codigo'] = '%' . $filters['codigo'] . '%';
                }
                
                // Filtro por nombre de producto
                if (isset($filters['nombre'])) {
                    $where[] = "p.nombre LIKE :nombre";
                    $params[':nombre'] = '%' . $filters['nombre'] . '%';
                }
                
                // Filtro por stock bajo
                if (isset($filters['stock_bajo']) && $filters['stock_bajo']) {
                    $where[] = "i.cantidad <= :stock_bajo_umbral";
                    $params[':stock_bajo_umbral'] = $filters['stock_bajo_umbral'] ?? 5; // Umbral por defecto
                }
                
                // Filtro por sin stock
                if (isset($filters['sin_stock']) && $filters['sin_stock']) {
                    $where[] = "i.cantidad = 0";
                }
            }
            
            // Aplicar condiciones WHERE si existen
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            // Ordenamiento
            if (isset($filters['orden'])) {
                switch ($filters['orden']) {
                    case 'producto_asc':
                        $sql .= " ORDER BY p.nombre ASC";
                        break;
                    case 'producto_desc':
                        $sql .= " ORDER BY p.nombre DESC";
                        break;
                    case 'stock_asc':
                        $sql .= " ORDER BY i.cantidad ASC";
                        break;
                    case 'stock_desc':
                        $sql .= " ORDER BY i.cantidad DESC";
                        break;
                    case 'almacen':
                        $sql .= " ORDER BY a.nombre ASC, p.nombre ASC";
                        break;
                    default:
                        $sql .= " ORDER BY p.nombre ASC";
                }
            } else {
                // Orden predeterminado
                $sql .= " ORDER BY a.nombre ASC, p.nombre ASC";
            }
            
            // Paginación
            $sql .= " LIMIT :limit OFFSET :offset";
            
            // Consulta para contar el total
            $countSql = "SELECT COUNT(*) FROM inventario i 
                         JOIN producto p ON i.id_producto = p.id_producto
                         JOIN almacen a ON i.id_almacen = a.id_almacen";
            
            if (!empty($where)) {
                $countSql .= " WHERE " . implode(' AND ', $where);
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
                        'nombre' => $row['producto_nombre']
                    ],
                    'almacen' => [
                        'id' => $row['id_almacen'],
                        'nombre' => $row['almacen_nombre']
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
            $this->logError('Error en getAll: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener inventario: ' . $e->getMessage(),
                'inventario' => []
            ];
        }
    }
    
    /**
     * Actualiza el stock de un producto
     * 
     * @param int $productId ID del producto
     * @param int $warehouseId ID del almacén
     * @param int $quantity Nueva cantidad
     * @param int $userId ID del usuario que realiza la actualización
     * @param string $reason Motivo de la actualización
     * @return array Resultado de la operación
     */
    public function updateStock($productId, $warehouseId, $quantity, $userId, $reason = '') {
        try {
            // Verificar que el producto existe
            $productCheckStmt = $this->db->prepare("SELECT COUNT(*) FROM producto WHERE id_producto = :id");
            $productCheckStmt->execute([':id' => $productId]);
            
            if ($productCheckStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ];
            }
            
            // Verificar que el almacén existe
            $warehouseCheckStmt = $this->db->prepare("SELECT COUNT(*) FROM almacen WHERE id_almacen = :id");
            $warehouseCheckStmt->execute([':id' => $warehouseId]);
            
            if ($warehouseCheckStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Almacén no encontrado'
                ];
            }
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Verificar si ya existe un registro de inventario para este producto y almacén
            $checkStmt = $this->db->prepare("
                SELECT id_inventario, cantidad 
                FROM inventario 
                WHERE id_producto = :id_producto AND id_almacen = :id_almacen
            ");
            
            $checkStmt->execute([
                ':id_producto' => $productId,
                ':id_almacen' => $warehouseId
            ]);
            
            if ($checkStmt->rowCount() > 0) {
                // Actualizar registro existente
                $inventoryData = $checkStmt->fetch(PDO::FETCH_ASSOC);
                $inventoryId = $inventoryData['id_inventario'];
                $oldQuantity = $inventoryData['cantidad'];
                
                $updateStmt = $this->db->prepare("
                    UPDATE inventario 
                    SET cantidad = :cantidad, fecha_actualizacion = NOW()
                    WHERE id_inventario = :id_inventario
                ");
                
                $updateStmt->execute([
                    ':id_inventario' => $inventoryId,
                    ':cantidad' => $quantity
                ]);
            } else {
                // Crear nuevo registro
                $insertStmt = $this->db->prepare("
                    INSERT INTO inventario (id_producto, id_almacen, cantidad, fecha_actualizacion)
                    VALUES (:id_producto, :id_almacen, :cantidad, NOW())
                ");
                
                $insertStmt->execute([
                    ':id_producto' => $productId,
                    ':id_almacen' => $warehouseId,
                    ':cantidad' => $quantity
                ]);
                
                $inventoryId = $this->db->lastInsertId();
                $oldQuantity = 0;
            }
            
            // Registrar movimiento en el histórico
            $movementType = ($quantity > $oldQuantity) ? 'ENTRADA' : 'SALIDA';
            
            // Preparar descripción si no se proporciona
            if (empty($reason)) {
                $reason = ($quantity > $oldQuantity) 
                    ? 'Actualización manual de inventario (aumento)'
                    : 'Actualización manual de inventario (disminución)';
            }
            
            $historyStmt = $this->db->prepare("
                INSERT INTO historico_stock (
                    id_producto, id_almacen, cantidad_anterior, cantidad_nueva,
                    tipo_movimiento, descripcion, fecha, id_usuario
                ) VALUES (
                    :id_producto, :id_almacen, :cantidad_anterior, :cantidad_nueva,
                    :tipo_movimiento, :descripcion, NOW(), :id_usuario
                )
            ");
            
            $historyStmt->execute([
                ':id_producto' => $productId,
                ':id_almacen' => $warehouseId,
                ':cantidad_anterior' => $oldQuantity,
                ':cantidad_nueva' => $quantity,
                ':tipo_movimiento' => $movementType,
                ':descripcion' => $reason,
                ':id_usuario' => $userId
            ]);
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Stock actualizado correctamente',
                'id_inventario' => $inventoryId,
                'cantidad_anterior' => $oldQuantity,
                'cantidad_nueva' => $quantity
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            $this->logError('Error en updateStock: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al actualizar stock: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Realiza un movimiento de stock entre almacenes
     * 
     * @param int $productId ID del producto
     * @param int $sourceWarehouseId ID del almacén origen
     * @param int $targetWarehouseId ID del almacén destino
     * @param int $quantity Cantidad a transferir
     * @param int $userId ID del usuario que realiza la operación
     * @param string $reason Motivo de la transferencia
     * @return array Resultado de la operación
     */
    public function transferStock($productId, $sourceWarehouseId, $targetWarehouseId, $quantity, $userId, $reason = '') {
        try {
            // Validaciones básicas
            if ($sourceWarehouseId == $targetWarehouseId) {
                return [
                    'success' => false,
                    'message' => 'El almacén origen y destino no pueden ser el mismo'
                ];
            }
            
            if ($quantity <= 0) {
                return [
                    'success' => false,
                    'message' => 'La cantidad a transferir debe ser mayor que cero'
                ];
            }
            
            // Verificar si hay suficiente stock en el almacén origen
            $sourceStockStmt = $this->db->prepare("
                SELECT cantidad 
                FROM inventario 
                WHERE id_producto = :id_producto AND id_almacen = :id_almacen
            ");
            
            $sourceStockStmt->execute([
                ':id_producto' => $productId,
                ':id_almacen' => $sourceWarehouseId
            ]);
            
            if ($sourceStockStmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'No hay stock del producto en el almacén origen'
                ];
            }
            
            $sourceStock = $sourceStockStmt->fetchColumn();
            
            if ($sourceStock < $quantity) {
                return [
                    'success' => false,
                    'message' => 'No hay suficiente stock en el almacén origen. Stock actual: ' . $sourceStock
                ];
            }
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Restar stock del almacén origen
            $sourceResult = $this->updateStock(
                $productId, 
                $sourceWarehouseId, 
                $sourceStock - $quantity,
                $userId,
                'Transferencia a almacén ID: ' . $targetWarehouseId . ($reason ? ' - ' . $reason : '')
            );
            
            if (!$sourceResult['success']) {
                // Si hay un error, revertir la transacción
                $this->db->rollBack();
                return $sourceResult;
            }
            
            // Verificar si ya existe un registro en el almacén destino
            $targetStockStmt = $this->db->prepare("
                SELECT cantidad 
                FROM inventario 
                WHERE id_producto = :id_producto AND id_almacen = :id_almacen
            ");
            
            $targetStockStmt->execute([
                ':id_producto' => $productId,
                ':id_almacen' => $targetWarehouseId
            ]);
            
            $targetStock = 0;
            if ($targetStockStmt->rowCount() > 0) {
                $targetStock = $targetStockStmt->fetchColumn();
            }
            
            // Sumar stock al almacén destino
            $targetResult = $this->updateStock(
                $productId, 
                $targetWarehouseId, 
                $targetStock + $quantity,
                $userId,
                'Transferencia desde almacén ID: ' . $sourceWarehouseId . ($reason ? ' - ' . $reason : '')
            );
            
            if (!$targetResult['success']) {
                // Si hay un error, revertir la transacción
                $this->db->rollBack();
                return $targetResult;
            }
            
            // Registrar la transferencia en una tabla específica si existe
            if ($this->tableExists('transferencia_stock')) {
                $transferStmt = $this->db->prepare("
                    INSERT INTO transferencia_stock (
                        id_producto, id_almacen_origen, id_almacen_destino,
                        cantidad, fecha, id_usuario, motivo
                    ) VALUES (
                        :id_producto, :id_almacen_origen, :id_almacen_destino,
                        :cantidad, NOW(), :id_usuario, :motivo
                    )
                ");
                
                $transferStmt->execute([
                    ':id_producto' => $productId,
                    ':id_almacen_origen' => $sourceWarehouseId,
                    ':id_almacen_destino' => $targetWarehouseId,
                    ':cantidad' => $quantity,
                    ':id_usuario' => $userId,
                    ':motivo' => $reason
                ]);
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Transferencia de stock realizada correctamente',
                'quantity' => $quantity,
                'source_stock_before' => $sourceStock,
                'source_stock_after' => $sourceStock - $quantity,
                'target_stock_before' => $targetStock,
                'target_stock_after' => $targetStock + $quantity
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            $this->logError('Error en transferStock: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al transferir stock: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene el historial de movimientos de stock
     * 
     * @param array $filters Filtros opcionales
     * @param int $limit Límite de resultados
     * @param int $offset Desplazamiento
     * @return array Historial de movimientos
     */
    public function getMovementHistory($filters = [], $limit = 50, $offset = 0) {
        try {
            // Construir consulta base
            $sql = "
                SELECT h.id, h.id_producto, h.id_almacen, h.cantidad_anterior, h.cantidad_nueva,
                       h.tipo_movimiento, h.descripcion, h.fecha, h.id_usuario,
                       p.codigo as producto_codigo, p.nombre as producto_nombre,
                       a.nombre as almacen_nombre,
                       CONCAT(u.nombres, ' ', u.apellidos) as usuario_nombre
                FROM historico_stock h
                JOIN producto p ON h.id_producto = p.id_producto
                JOIN almacen a ON h.id_almacen = a.id_almacen
                LEFT JOIN usuario u ON h.id_usuario = u.id_usuario
            ";
            
            $where = [];
            $params = [];
            
            // Aplicar filtros
            if (!empty($filters)) {
                // Filtro por producto
                if (isset($filters['id_producto'])) {
                    $where[] = "h.id_producto = :id_producto";
                    $params[':id_producto'] = $filters['id_producto'];
                }
                
                // Filtro por almacén
                if (isset($filters['id_almacen'])) {
                    $where[] = "h.id_almacen = :id_almacen";
                    $params[':id_almacen'] = $filters['id_almacen'];
                }
                
                // Filtro por tipo de movimiento
                if (isset($filters['tipo_movimiento'])) {
                    $where[] = "h.tipo_movimiento = :tipo_movimiento";
                    $params[':tipo_movimiento'] = $filters['tipo_movimiento'];
                }
                
                // Filtro por usuario
                if (isset($filters['id_usuario'])) {
                    $where[] = "h.id_usuario = :id_usuario";
                    $params[':id_usuario'] = $filters['id_usuario'];
                }
                
                // Filtro por fecha desde
                if (isset($filters['fecha_desde'])) {
                    $where[] = "h.fecha >= :fecha_desde";
                    $params[':fecha_desde'] = $filters['fecha_desde'] . ' 00:00:00';
                }
                
                // Filtro por fecha hasta
                if (isset($filters['fecha_hasta'])) {
                    $where[] = "h.fecha <= :fecha_hasta";
                    $params[':fecha_hasta'] = $filters['fecha_hasta'] . ' 23:59:59';
                }
            }
            
            // Aplicar condiciones WHERE si existen
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            // Ordenamiento (por defecto, más reciente primero)
            $sql .= " ORDER BY h.fecha DESC, h.id DESC";
            
            // Paginación
            $sql .= " LIMIT :limit OFFSET :offset";
            
            // Consulta para contar el total
            $countSql = "SELECT COUNT(*) FROM historico_stock h";
            
            if (!empty($where)) {
                $countSql .= " WHERE " . implode(' AND ', $where);
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
            $movements = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $movements[] = [
                    'id' => $row['id'],
                    'producto' => [
                        'id' => $row['id_producto'],
                        'codigo' => $row['producto_codigo'],
                        'nombre' => $row['producto_nombre']
                    ],
                    'almacen' => [
                        'id' => $row['id_almacen'],
                        'nombre' => $row['almacen_nombre']
                    ],
                    'cantidad_anterior' => $row['cantidad_anterior'],
                    'cantidad_nueva' => $row['cantidad_nueva'],
                    'diferencia' => $row['cantidad_nueva'] - $row['cantidad_anterior'],
                    'tipo_movimiento' => $row['tipo_movimiento'],
                    'descripcion' => $row['descripcion'],
                    'fecha' => $row['fecha'],
                    'usuario' => [
                        'id' => $row['id_usuario'],
                        'nombre' => $row['usuario_nombre'] ?: 'Sistema'
                    ]
                ];
            }
            
            return [
                'success' => true,
                'movimientos' => $movements,
                'total' => $totalItems,
                'paginas' => ceil($totalItems / $limit)
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getMovementHistory: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener historial de movimientos: ' . $e->getMessage(),
                'movimientos' => []
            ];
        }
    }
    
    /**
     * Obtiene productos con stock bajo
     * 
     * @param int $threshold Umbral de stock bajo
     * @param int|null $warehouseId ID del almacén (opcional)
     * @return array Productos con stock bajo
     */
    public function getLowStockProducts($threshold = 5, $warehouseId = null) {
        try {
            // Construir consulta base
            $sql = "
                SELECT i.id_producto, i.id_almacen, i.cantidad, 
                       p.codigo as producto_codigo, p.nombre as producto_nombre,
                       a.nombre as almacen_nombre
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                JOIN almacen a ON i.id_almacen = a.id_almacen
                WHERE i.cantidad <= :threshold AND i.cantidad > 0
                AND p.estado = 1
            ";
            
            $params = [':threshold' => $threshold];
            
            // Filtrar por almacén si se especifica
            if ($warehouseId !== null) {
                $sql .= " AND i.id_almacen = :id_almacen";
                $params[':id_almacen'] = $warehouseId;
            }
            
            // Ordenar por cantidad ascendente (menor stock primero)
            $sql .= " ORDER BY i.cantidad ASC, p.nombre ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[] = [
                    'id_producto' => $row['id_producto'],
                    'codigo' => $row['producto_codigo'],
                    'nombre' => $row['producto_nombre'],
                    'id_almacen' => $row['id_almacen'],
                    'almacen' => $row['almacen_nombre'],
                    'cantidad' => $row['cantidad']
                ];
            }
            
            return [
                'success' => true,
                'productos' => $products,
                'total' => count($products)
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getLowStockProducts: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener productos con stock bajo: ' . $e->getMessage(),
                'productos' => []
            ];
        }
    }
    
    /**
     * Obtiene productos sin stock
     * 
     * @param int|null $warehouseId ID del almacén (opcional)
     * @return array Productos sin stock
     */
    public function getOutOfStockProducts($warehouseId = null) {
        try {
            // Consulta para productos totalmente sin stock (ni siquiera tienen registro en inventario)
            $productsWithNoInventory = "
                SELECT p.id_producto, p.codigo, p.nombre, 
                       NULL as id_almacen, 'Sin registros de inventario' as mensaje
                FROM producto p
                WHERE p.estado = 1
                AND NOT EXISTS (
                    SELECT 1 FROM inventario i WHERE i.id_producto = p.id_producto
                )
            ";
            
            // Consulta para productos con registro de inventario pero cantidad cero
            $productsWithZeroInventory = "
                SELECT p.id_producto, p.codigo, p.nombre, 
                       i.id_almacen, a.nombre as almacen_nombre
                FROM producto p
                JOIN inventario i ON p.id_producto = i.id_producto
                JOIN almacen a ON i.id_almacen = a.id_almacen
                WHERE p.estado = 1 AND i.cantidad = 0
            ";
            
            // Si se especifica un almacén, filtrar la segunda consulta
            if ($warehouseId !== null) {
                $productsWithZeroInventory .= " AND i.id_almacen = " . intval($warehouseId);
            }
            
            // Combinar las consultas con UNION
            $sql = $productsWithNoInventory . " UNION " . $productsWithZeroInventory . " ORDER BY nombre ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[] = [
                    'id_producto' => $row['id_producto'],
                    'codigo' => $row['codigo'],
                    'nombre' => $row['nombre'],
                    'id_almacen' => $row['id_almacen'],
                    'almacen' => $row['almacen_nombre'] ?? $row['mensaje']
                ];
            }
            
            return [
                'success' => true,
                'productos' => $products,
                'total' => count($products)
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getOutOfStockProducts: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener productos sin stock: ' . $e->getMessage(),
                'productos' => []
            ];
        }
    }
    
    /**
     * Verifica si una tabla existe en la base de datos
     * 
     * @param string $tableName Nombre de la tabla
     * @return bool True si existe, false si no
     */
    private function tableExists($tableName) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                AND table_name = :table_name
            ");
            
            $stmt->execute([':table_name' => $tableName]);
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            $this->logError('Error en tableExists: ' . $e->getMessage());
            return false;
        }
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
        
        $logFile = $logDir . '/inventory_model_' . date('Y-m-d') . '.log';
        $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}