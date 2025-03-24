<?php
/**
 * Modelo de Subcategoría
 * Gestiona todas las operaciones relacionadas con subcategorías de productos
 */
class subcategory {
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
     * Obtiene todas las subcategorías
     * 
     * @param int|null $categoryId Filtrar por categoría (opcional)
     * @param bool $includeInactive Incluir subcategorías inactivas
     * @return array Subcategorías
     */
    public function getAll($categoryId = null, $includeInactive = false) {
        try {
            $sql = "
                SELECT sc.id_subcategoria, sc.nombre, sc.descripcion, sc.imagen, sc.slug, 
                       sc.id_categoria, sc.estado, sc.orden, sc.fecha_creacion, sc.fecha_actualizacion,
                       c.nombre as categoria_nombre,
                       (SELECT COUNT(*) FROM producto p WHERE p.id_subcategoria = sc.id_subcategoria) as total_productos
                FROM subcategoria sc
                JOIN categoria c ON sc.id_categoria = c.id_categoria
            ";
            
            $params = [];
            
            // Filtrar por categoría si se especifica
            if ($categoryId !== null) {
                $sql .= " WHERE sc.id_categoria = :id_categoria";
                $params[':id_categoria'] = $categoryId;
                
                // Agregar filtro de estado si es necesario
                if (!$includeInactive) {
                    $sql .= " AND sc.estado = 1";
                }
            } else if (!$includeInactive) {
                // Solo filtrar por estado si no se filtra por categoría
                $sql .= " WHERE sc.estado = 1";
            }
            
            $sql .= " ORDER BY c.nombre ASC, sc.orden ASC, sc.nombre ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $subcategories = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $subcategories[] = [
                    'id' => $row['id_subcategoria'],
                    'nombre' => $row['nombre'],
                    'descripcion' => $row['descripcion'],
                    'imagen' => $row['imagen'],
                    'slug' => $row['slug'],
                    'id_categoria' => $row['id_categoria'],
                    'categoria_nombre' => $row['categoria_nombre'],
                    'estado' => $row['estado'],
                    'orden' => $row['orden'],
                    'fecha_creacion' => $row['fecha_creacion'],
                    'fecha_actualizacion' => $row['fecha_actualizacion'],
                    'total_productos' => $row['total_productos']
                ];
            }
            
            return [
                'success' => true,
                'subcategorias' => $subcategories
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getAll: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener subcategorías: ' . $e->getMessage(),
                'subcategorias' => []
            ];
        }
    }
    
    /**
     * Obtiene una subcategoría por su ID
     * 
     * @param int $id ID de la subcategoría
     * @return array Datos de la subcategoría
     */
    public function getById($id) {
        try {
            $sql = "
                SELECT sc.id_subcategoria, sc.nombre, sc.descripcion, sc.imagen, sc.slug, 
                       sc.id_categoria, sc.estado, sc.orden, sc.fecha_creacion, sc.fecha_actualizacion,
                       c.nombre as categoria_nombre
                FROM subcategoria sc
                JOIN categoria c ON sc.id_categoria = c.id_categoria
                WHERE sc.id_subcategoria = :id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Subcategoría no encontrada'
                ];
            }
            
            $subcategory = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener productos de la subcategoría
            $productsStmt = $this->db->prepare("
                SELECT p.id_producto, p.nombre, p.descripcion, p.precio_base,
                       (SELECT ruta FROM producto_imagen WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as imagen
                FROM producto p
                WHERE p.id_subcategoria = :id_subcategoria AND p.estado = 1
                ORDER BY p.destacado DESC, p.fecha_actualizacion DESC
                LIMIT 10
            ");
            
            $productsStmt->execute([':id_subcategoria' => $id]);
            $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'subcategoria' => [
                    'id' => $subcategory['id_subcategoria'],
                    'nombre' => $subcategory['nombre'],
                    'descripcion' => $subcategory['descripcion'],
                    'imagen' => $subcategory['imagen'],
                    'slug' => $subcategory['slug'],
                    'id_categoria' => $subcategory['id_categoria'],
                    'categoria_nombre' => $subcategory['categoria_nombre'],
                    'estado' => $subcategory['estado'],
                    'orden' => $subcategory['orden'],
                    'fecha_creacion' => $subcategory['fecha_creacion'],
                    'fecha_actualizacion' => $subcategory['fecha_actualizacion'],
                    'productos' => $products
                ]
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getById: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener la subcategoría: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene una subcategoría por su slug
     * 
     * @param string $slug Slug de la subcategoría
     * @return array Datos de la subcategoría
     */
    public function getBySlug($slug) {
        try {
            $sql = "
                SELECT id_subcategoria FROM subcategoria
                WHERE slug = :slug
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Subcategoría no encontrada'
                ];
            }
            
            $subcategoryId = $stmt->fetchColumn();
            
            // Usar getById para obtener todos los datos
            return $this->getById($subcategoryId);
            
        } catch (PDOException $e) {
            $this->logError('Error en getBySlug: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener la subcategoría: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crea una nueva subcategoría
     * 
     * @param array $subcategoryData Datos de la subcategoría
     * @return array Resultado de la operación
     */
    public function create($subcategoryData) {
        try {
            // Validar datos
            if (empty($subcategoryData['nombre']) || empty($subcategoryData['id_categoria'])) {
                return [
                    'success' => false,
                    'message' => 'El nombre y la categoría son campos requeridos'
                ];
            }
            
            // Verificar que la categoría exista
            $checkCategoryStmt = $this->db->prepare("SELECT COUNT(*) FROM categoria WHERE id_categoria = :id_categoria");
            $checkCategoryStmt->execute([':id_categoria' => $subcategoryData['id_categoria']]);
            
            if ($checkCategoryStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'La categoría no existe'
                ];
            }
            
            // Generar slug si no se proporciona
            if (empty($subcategoryData['slug'])) {
                $subcategoryData['slug'] = $this->generateSlug($subcategoryData['nombre']);
            }
            
            // Verificar que el slug sea único
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM subcategoria WHERE slug = :slug");
            $checkStmt->execute([':slug' => $subcategoryData['slug']]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'Ya existe una subcategoría con ese slug'
                ];
            }
            
            // Obtener orden máximo para la categoría
            $maxOrderStmt = $this->db->prepare("
                SELECT MAX(orden) FROM subcategoria WHERE id_categoria = :id_categoria
            ");
            $maxOrderStmt->execute([':id_categoria' => $subcategoryData['id_categoria']]);
            $maxOrder = $maxOrderStmt->fetchColumn() ?: 0;
            
            // Insertar subcategoría
            $stmt = $this->db->prepare("
                INSERT INTO subcategoria (
                    nombre, descripcion, imagen, slug, id_categoria,
                    estado, orden, fecha_creacion, fecha_actualizacion
                ) VALUES (
                    :nombre, :descripcion, :imagen, :slug, :id_categoria,
                    :estado, :orden, NOW(), NOW()
                )
            ");
            
            $stmt->execute([
                ':nombre' => $subcategoryData['nombre'],
                ':descripcion' => $subcategoryData['descripcion'] ?? '',
                ':imagen' => $subcategoryData['imagen'] ?? '',
                ':slug' => $subcategoryData['slug'],
                ':id_categoria' => $subcategoryData['id_categoria'],
                ':estado' => $subcategoryData['estado'] ?? 1,
                ':orden' => $subcategoryData['orden'] ?? ($maxOrder + 1)
            ]);
            
            $subcategoryId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Subcategoría creada correctamente',
                'id' => $subcategoryId
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en create: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al crear la subcategoría: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualiza una subcategoría existente
     * 
     * @param int $id ID de la subcategoría
     * @param array $subcategoryData Datos de la subcategoría
     * @return array Resultado de la operación
     */
    public function update($id, $subcategoryData) {
        try {
            // Verificar si la subcategoría existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM subcategoria WHERE id_subcategoria = :id");
            $checkStmt->execute([':id' => $id]);
            
            if ($checkStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Subcategoría no encontrada'
                ];
            }
            
            // Si se actualiza la categoría, verificar que exista
            if (isset($subcategoryData['id_categoria'])) {
                $checkCategoryStmt = $this->db->prepare("SELECT COUNT(*) FROM categoria WHERE id_categoria = :id_categoria");
                $checkCategoryStmt->execute([':id_categoria' => $subcategoryData['id_categoria']]);
                
                if ($checkCategoryStmt->fetchColumn() == 0) {
                    return [
                        'success' => false,
                        'message' => 'La categoría no existe'
                    ];
                }
            }
            
            // Si se actualiza el slug, verificar que sea único
            if (isset($subcategoryData['slug'])) {
                $checkSlugStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM subcategoria 
                    WHERE slug = :slug AND id_subcategoria != :id
                ");
                
                $checkSlugStmt->execute([
                    ':slug' => $subcategoryData['slug'],
                    ':id' => $id
                ]);
                
                if ($checkSlugStmt->fetchColumn() > 0) {
                    return [
                        'success' => false,
                        'message' => 'Ya existe otra subcategoría con ese slug'
                    ];
                }
            }
            
            // Si se actualiza el nombre pero no el slug, generar nuevo slug
            if (isset($subcategoryData['nombre']) && !isset($subcategoryData['slug'])) {
                $subcategoryData['slug'] = $this->generateSlug($subcategoryData['nombre']);
                
                // Verificar que el nuevo slug sea único
                $checkSlugStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM subcategoria 
                    WHERE slug = :slug AND id_subcategoria != :id
                ");
                
                $checkSlugStmt->execute([
                    ':slug' => $subcategoryData['slug'],
                    ':id' => $id
                ]);
                
                if ($checkSlugStmt->fetchColumn() > 0) {
                    // Agregar un sufijo numérico al slug para hacerlo único
                    $subcategoryData['slug'] = $subcategoryData['slug'] . '-' . $id;
                }
            }
            
            // Preparar campos a actualizar
            $updateFields = [];
            $params = [':id' => $id];
            
            $possibleFields = [
                'nombre', 'descripcion', 'imagen', 'slug', 
                'id_categoria', 'estado', 'orden'
            ];
            
            foreach ($possibleFields as $field) {
                if (isset($subcategoryData[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[":$field"] = $subcategoryData[$field];
                }
            }
            
            // Agregar fecha de actualización
            $updateFields[] = "fecha_actualizacion = NOW()";
            
            // Si hay campos para actualizar
            if (!empty($updateFields)) {
                $sql = "UPDATE subcategoria SET " . implode(', ', $updateFields) . " WHERE id_subcategoria = :id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
            
            return [
                'success' => true,
                'message' => 'Subcategoría actualizada correctamente'
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en update: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al actualizar la subcategoría: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Elimina una subcategoría
     * 
     * @param int $id ID de la subcategoría
     * @return array Resultado de la operación
     */
    public function delete($id) {
        try {
            // Verificar si la subcategoría existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM subcategoria WHERE id_subcategoria = :id");
            $checkStmt->execute([':id' => $id]);
            
            if ($checkStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Subcategoría no encontrada'
                ];
            }
            
            // Verificar si hay productos asociados
            $productCheckStmt = $this->db->prepare("
                SELECT COUNT(*) FROM producto WHERE id_subcategoria = :id
            ");
            $productCheckStmt->execute([':id' => $id]);
            
            if ($productCheckStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar la subcategoría porque tiene productos asociados'
                ];
            }
            
            // Eliminar la subcategoría
            $deleteStmt = $this->db->prepare("DELETE FROM subcategoria WHERE id_subcategoria = :id");
            $deleteStmt->execute([':id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Subcategoría eliminada correctamente'
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en delete: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al eliminar la subcategoría: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Reordena las subcategorías de una categoría
     * 
     * @param int $categoryId ID de la categoría
     * @param array $orderData Array de [id => orden]
     * @return array Resultado de la operación
     */
    public function reorder($categoryId, $orderData) {
        try {
            // Verificar si la categoría existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM categoria WHERE id_categoria = :id");
            $checkStmt->execute([':id' => $categoryId]);
            
            if ($checkStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ];
            }
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                UPDATE subcategoria 
                SET orden = :orden, fecha_actualizacion = NOW()
                WHERE id_subcategoria = :id AND id_categoria = :id_categoria
            ");
            
            foreach ($orderData as $id => $order) {
                $stmt->execute([
                    ':id' => $id,
                    ':id_categoria' => $categoryId,
                    ':orden' => $order
                ]);
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Subcategorías reordenadas correctamente'
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            $this->logError('Error en reorder: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al reordenar las subcategorías: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cambia el estado de una subcategoría (activar/desactivar)
     * 
     * @param int $id ID de la subcategoría
     * @param int $estado Nuevo estado (1=activo, 0=inactivo)
     * @return array Resultado de la operación
     */
    public function changeState($id, $estado) {
        try {
            $stmt = $this->db->prepare("
                UPDATE subcategoria 
                SET estado = :estado, fecha_actualizacion = NOW()
                WHERE id_subcategoria = :id
            ");
            
            $stmt->execute([
                ':id' => $id,
                ':estado' => $estado ? 1 : 0
            ]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Subcategoría no encontrada'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Estado de la subcategoría actualizado correctamente'
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en changeState: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado de la subcategoría: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene todos los productos de una subcategoría
     * 
     * @param int $subcategoryId ID de la subcategoría
     * @param int $limit Límite de resultados
     * @param int $offset Desplazamiento
     * @param array $filters Filtros adicionales
     * @return array Productos de la subcategoría
     */
    public function getProducts($subcategoryId, $limit = 12, $offset = 0, $filters = []) {
        try {
            // Verificar si la subcategoría existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM subcategoria WHERE id_subcategoria = :id");
            $checkStmt->execute([':id' => $subcategoryId]);
            
            if ($checkStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Subcategoría no encontrada',
                    'productos' => []
                ];
            }
            
            // Construir consulta base
            $sql = "
                SELECT p.id_producto, p.codigo, p.nombre, p.descripcion, p.precio_base,
                       p.estado, p.destacado, p.fecha_creacion, p.fecha_actualizacion,
                       c.id_categoria, c.nombre as categoria_nombre,
                       (SELECT ruta FROM producto_imagen WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as imagen,
                       (SELECT SUM(cantidad) FROM inventario WHERE id_producto = p.id_producto) as stock_total
                FROM producto p
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                WHERE p.id_subcategoria = :id_subcategoria
            ";
            
            $params = [':id_subcategoria' => $subcategoryId];
            
            // Agregar filtros adicionales
            if (!empty($filters)) {
                // Filtro por precio mínimo
                if (isset($filters['precio_min'])) {
                    $sql .= " AND p.precio_base >= :precio_min";
                    $params[':precio_min'] = $filters['precio_min'];
                }
                
                // Filtro por precio máximo
                if (isset($filters['precio_max'])) {
                    $sql .= " AND p.precio_base <= :precio_max";
                    $params[':precio_max'] = $filters['precio_max'];
                }
                
                // Solo productos activos
                if (isset($filters['solo_activos']) && $filters['solo_activos']) {
                    $sql .= " AND p.estado = 1";
                }
                
                // Solo productos con stock
                if (isset($filters['con_stock']) && $filters['con_stock']) {
                    $sql .= " AND (SELECT SUM(cantidad) FROM inventario WHERE id_producto = p.id_producto) > 0";
                }
            } else {
                // Por defecto, solo productos activos
                $sql .= " AND p.estado = 1";
            }
            
            // Ordenamiento
            $orderBy = "p.destacado DESC, p.fecha_actualizacion DESC"; // Valor por defecto
            if (isset($filters['orden'])) {
                switch ($filters['orden']) {
                    case 'nombre_asc':
                        $orderBy = "p.nombre ASC";
                        break;
                    case 'nombre_desc':
                        $orderBy = "p.nombre DESC";
                        break;
                    case 'precio_asc':
                        $orderBy = "p.precio_base ASC";
                        break;
                    case 'precio_desc':
                        $orderBy = "p.precio_base DESC";
                        break;
                    case 'mas_vendido':
                        $sql = str_replace('LEFT JOIN categoria c', 'LEFT JOIN categoria c
                        LEFT JOIN pedido_item pi ON p.id_producto = pi.id_producto', $sql);
                        $orderBy = "COUNT(pi.id_pedido_item) DESC";
                        $sql .= " GROUP BY p.id_producto";
                        break;
                }
            }
            
            $sql .= " ORDER BY " . $orderBy;
            
            // Paginación
            $sql .= " LIMIT :limit OFFSET :offset";
            
            // Consulta para contar el total
            $countSql = str_replace("SELECT p.id_producto, p.codigo, p.nombre", "SELECT COUNT(*)", $sql);
            $countSql = preg_replace('/LIMIT\s+:\w+\s+OFFSET\s+:\w+/i', '', $countSql);
            
            // Ejecutar consulta de conteo
            $countStmt = $this->db->prepare($countSql);
            
            // Bindear parámetros para el conteo
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            
            $countStmt->execute();
            $totalProducts = $countStmt->fetchColumn();
            
            // Calcular número total de páginas
            $totalPages = ceil($totalProducts / $limit);
            
            // Ejecutar consulta principal
            $stmt = $this->db->prepare($sql);
            
            // Bindear parámetros de limit y offset
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            // Bindear los demás parámetros
            foreach ($params as $key => $value) {
                if ($key !== ':limit' && $key !== ':offset') {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            
            // Preparar resultados
            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[] = [
                    'id' => $row['id_producto'],
                    'codigo' => $row['codigo'],
                    'nombre' => $row['nombre'],
                    'descripcion' => $row['descripcion'],
                    'precio_base' => $row['precio_base'],
                    'estado' => $row['estado'],
                    'destacado' => $row['destacado'],
                    'fecha_creacion' => $row['fecha_creacion'],
                    'fecha_actualizacion' => $row['fecha_actualizacion'],
                    'categoria' => [
                        'id' => $row['id_categoria'],
                        'nombre' => $row['categoria_nombre']
                    ],
                    'imagen' => $row['imagen'],
                    'stock_total' => $row['stock_total']
                ];
            }
            
            return [
                'success' => true,
                'productos' => $products,
                'total' => $totalProducts,
                'paginas' => $totalPages
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getProducts: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener productos de la subcategoría: ' . $e->getMessage(),
                'productos' => []
            ];
        }
    }
    
    /**
     * Genera un slug a partir de un texto
     * 
     * @param string $text Texto a convertir en slug
     * @return string Slug generado
     */
    private function generateSlug($text) {
        // Reemplazar caracteres especiales y acentos
        $text = $this->removeAccents($text);
        
        // Convertir a minúsculas
        $text = strtolower($text);
        
        // Reemplazar espacios y otros caracteres no alfanuméricos con guiones
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        
        // Eliminar guiones al inicio y final
        $text = trim($text, '-');
        
        return $text;
    }
    
    /**
     * Elimina acentos y caracteres especiales
     * 
     * @param string $text Texto con acentos
     * @return string Texto sin acentos
     */
    private function removeAccents($text) {
        $search = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'];
        $replace = ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'A', 'E', 'I', 'O', 'U', 'U', 'N'];
        
        return str_replace($search, $replace, $text);
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
        
        $logFile = $logDir . '/subcategory_model_' . date('Y-m-d') . '.log';
        $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}