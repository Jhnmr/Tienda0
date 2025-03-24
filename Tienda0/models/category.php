<?php
/**
 * Modelo de Categoría
 * Gestiona todas las operaciones relacionadas con categorías de productos
 */
class category {
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
     * Obtiene todas las categorías
     * 
     * @param bool $includeInactive Incluir categorías inactivas
     * @return array Categorías
     */
    public function getAll($includeInactive = false) {
        try {
            $sql = "
                SELECT c.id_categoria, c.nombre, c.descripcion, c.imagen, c.slug, 
                       c.estado, c.destacada, c.orden, c.fecha_creacion, c.fecha_actualizacion,
                       (SELECT COUNT(*) FROM producto p WHERE p.id_categoria = c.id_categoria) as total_productos
                FROM categoria c
            ";
            
            if (!$includeInactive) {
                $sql .= " WHERE c.estado = 1";
            }
            
            $sql .= " ORDER BY c.orden ASC, c.nombre ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $categories = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = [
                    'id' => $row['id_categoria'],
                    'nombre' => $row['nombre'],
                    'descripcion' => $row['descripcion'],
                    'imagen' => $row['imagen'],
                    'slug' => $row['slug'],
                    'estado' => $row['estado'],
                    'destacada' => $row['destacada'],
                    'orden' => $row['orden'],
                    'fecha_creacion' => $row['fecha_creacion'],
                    'fecha_actualizacion' => $row['fecha_actualizacion'],
                    'total_productos' => $row['total_productos']
                ];
            }
            
            return [
                'success' => true,
                'categorias' => $categories
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getAll: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener categorías: ' . $e->getMessage(),
                'categorias' => []
            ];
        }
    }
    
    /**
     * Obtiene categorías destacadas
     * 
     * @param int $limit Límite de resultados
     * @return array Categorías destacadas
     */
    public function getFeatured($limit = 5) {
        try {
            $sql = "
                SELECT c.id_categoria, c.nombre, c.descripcion, c.imagen, c.slug,
                       (SELECT COUNT(*) FROM producto p WHERE p.id_categoria = c.id_categoria) as total_productos
                FROM categoria c
                WHERE c.destacada = 1 AND c.estado = 1
                ORDER BY c.orden ASC
                LIMIT :limit
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $categories = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = [
                    'id' => $row['id_categoria'],
                    'nombre' => $row['nombre'],
                    'descripcion' => $row['descripcion'],
                    'imagen' => $row['imagen'],
                    'slug' => $row['slug'],
                    'total_productos' => $row['total_productos']
                ];
            }
            
            return [
                'success' => true,
                'categorias' => $categories
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getFeatured: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener categorías destacadas: ' . $e->getMessage(),
                'categorias' => []
            ];
        }
    }
    
    /**
     * Obtiene una categoría por su ID
     * 
     * @param int $id ID de la categoría
     * @return array Datos de la categoría
     */
    public function getById($id) {
        try {
            $sql = "
                SELECT c.id_categoria, c.nombre, c.descripcion, c.imagen, c.slug, 
                       c.estado, c.destacada, c.orden, c.fecha_creacion, c.fecha_actualizacion
                FROM categoria c
                WHERE c.id_categoria = :id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ];
            }
            
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener subcategorías
            $subcategoriesStmt = $this->db->prepare("
                SELECT id_subcategoria, nombre, descripcion, slug, estado
                FROM subcategoria
                WHERE id_categoria = :id_categoria
                ORDER BY orden ASC, nombre ASC
            ");
            
            $subcategoriesStmt->execute([':id_categoria' => $id]);
            $subcategories = $subcategoriesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener productos de la categoría
            $productsStmt = $this->db->prepare("
                SELECT p.id_producto, p.nombre, p.descripcion, p.precio_base,
                       (SELECT ruta FROM producto_imagen WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as imagen
                FROM producto p
                WHERE p.id_categoria = :id_categoria AND p.estado = 1
                ORDER BY p.destacado DESC, p.fecha_actualizacion DESC
                LIMIT 10
            ");
            
            $productsStmt->execute([':id_categoria' => $id]);
            $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'categoria' => [
                    'id' => $category['id_categoria'],
                    'nombre' => $category['nombre'],
                    'descripcion' => $category['descripcion'],
                    'imagen' => $category['imagen'],
                    'slug' => $category['slug'],
                    'estado' => $category['estado'],
                    'destacada' => $category['destacada'],
                    'orden' => $category['orden'],
                    'fecha_creacion' => $category['fecha_creacion'],
                    'fecha_actualizacion' => $category['fecha_actualizacion'],
                    'subcategorias' => $subcategories,
                    'productos' => $products
                ]
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getById: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener la categoría: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene una categoría por su slug
     * 
     * @param string $slug Slug de la categoría
     * @return array Datos de la categoría
     */
    public function getBySlug($slug) {
        try {
            $sql = "
                SELECT id_categoria FROM categoria
                WHERE slug = :slug
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ];
            }
            
            $categoryId = $stmt->fetchColumn();
            
            // Usar getById para obtener todos los datos
            return $this->getById($categoryId);
            
        } catch (PDOException $e) {
            $this->logError('Error en getBySlug: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener la categoría: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crea una nueva categoría
     * 
     * @param array $categoryData Datos de la categoría
     * @return array Resultado de la operación
     */
    public function create($categoryData) {
        try {
            // Validar datos
            if (empty($categoryData['nombre'])) {
                return [
                    'success' => false,
                    'message' => 'El nombre de la categoría es requerido'
                ];
            }
            
            // Generar slug si no se proporciona
            if (empty($categoryData['slug'])) {
                $categoryData['slug'] = $this->generateSlug($categoryData['nombre']);
            }
            
            // Verificar que el slug sea único
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM categoria WHERE slug = :slug");
            $checkStmt->execute([':slug' => $categoryData['slug']]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'Ya existe una categoría con ese slug'
                ];
            }
            
            // Obtener el orden máximo actual
            $maxOrderStmt = $this->db->prepare("SELECT MAX(orden) FROM categoria");
            $maxOrderStmt->execute();
            $maxOrder = $maxOrderStmt->fetchColumn() ?: 0;
            
            // Insertar categoría
            $stmt = $this->db->prepare("
                INSERT INTO categoria (
                    nombre, descripcion, imagen, slug, 
                    estado, destacada, orden, fecha_creacion, fecha_actualizacion
                ) VALUES (
                    :nombre, :descripcion, :imagen, :slug, 
                    :estado, :destacada, :orden, NOW(), NOW()
                )
            ");
            
            $stmt->execute([
                ':nombre' => $categoryData['nombre'],
                ':descripcion' => $categoryData['descripcion'] ?? '',
                ':imagen' => $categoryData['imagen'] ?? '',
                ':slug' => $categoryData['slug'],
                ':estado' => $categoryData['estado'] ?? 1,
                ':destacada' => $categoryData['destacada'] ?? 0,
                ':orden' => $categoryData['orden'] ?? ($maxOrder + 1)
            ]);
            
            $categoryId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Categoría creada correctamente',
                'id' => $categoryId
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en create: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al crear la categoría: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualiza una categoría existente
     * 
     * @param int $id ID de la categoría
     * @param array $categoryData Datos de la categoría
     * @return array Resultado de la operación
     */
    public function update($id, $categoryData) {
        try {
            // Verificar si la categoría existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM categoria WHERE id_categoria = :id");
            $checkStmt->execute([':id' => $id]);
            
            if ($checkStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ];
            }
            
            // Si se actualiza el slug, verificar que sea único
            if (isset($categoryData['slug'])) {
                $checkSlugStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM categoria 
                    WHERE slug = :slug AND id_categoria != :id
                ");
                
                $checkSlugStmt->execute([
                    ':slug' => $categoryData['slug'],
                    ':id' => $id
                ]);
                
                if ($checkSlugStmt->fetchColumn() > 0) {
                    return [
                        'success' => false,
                        'message' => 'Ya existe otra categoría con ese slug'
                    ];
                }
            }
            
            // Si se actualiza el nombre pero no el slug, generar nuevo slug
            if (isset($categoryData['nombre']) && !isset($categoryData['slug'])) {
                $categoryData['slug'] = $this->generateSlug($categoryData['nombre']);
                
                // Verificar que el nuevo slug sea único
                $checkSlugStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM categoria 
                    WHERE slug = :slug AND id_categoria != :id
                ");
                
                $checkSlugStmt->execute([
                    ':slug' => $categoryData['slug'],
                    ':id' => $id
                ]);
                
                if ($checkSlugStmt->fetchColumn() > 0) {
                    // Agregar un sufijo numérico al slug para hacerlo único
                    $categoryData['slug'] = $categoryData['slug'] . '-' . $id;
                }
            }
            
            // Preparar campos a actualizar
            $updateFields = [];
            $params = [':id' => $id];
            
            $possibleFields = [
                'nombre', 'descripcion', 'imagen', 'slug', 
                'estado', 'destacada', 'orden'
            ];
            
            foreach ($possibleFields as $field) {
                if (isset($categoryData[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[":$field"] = $categoryData[$field];
                }
            }
            
            // Agregar fecha de actualización
            $updateFields[] = "fecha_actualizacion = NOW()";
            
            // Si hay campos para actualizar
            if (!empty($updateFields)) {
                $sql = "UPDATE categoria SET " . implode(', ', $updateFields) . " WHERE id_categoria = :id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
            
            return [
                'success' => true,
                'message' => 'Categoría actualizada correctamente'
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en update: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al actualizar la categoría: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Elimina una categoría
     * 
     * @param int $id ID de la categoría
     * @return array Resultado de la operación
     */
    public function delete($id) {
        try {
            // Verificar si la categoría existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM categoria WHERE id_categoria = :id");
            $checkStmt->execute([':id' => $id]);
            
            if ($checkStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ];
            }
            
            // Verificar si hay productos asociados
            $productCheckStmt = $this->db->prepare("
                SELECT COUNT(*) FROM producto WHERE id_categoria = :id
            ");
            $productCheckStmt->execute([':id' => $id]);
            
            if ($productCheckStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar la categoría porque tiene productos asociados'
                ];
            }
            
            // Verificar si hay subcategorías
            $subcategoryCheckStmt = $this->db->prepare("
                SELECT COUNT(*) FROM subcategoria WHERE id_categoria = :id
            ");
            $subcategoryCheckStmt->execute([':id' => $id]);
            
            if ($subcategoryCheckStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar la categoría porque tiene subcategorías asociadas'
                ];
            }
            
            // Eliminar la categoría
            $deleteStmt = $this->db->prepare("DELETE FROM categoria WHERE id_categoria = :id");
            $deleteStmt->execute([':id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Categoría eliminada correctamente'
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en delete: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al eliminar la categoría: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Reordena las categorías
     * 
     * @param array $orderData Array de [id => orden]
     * @return array Resultado de la operación
     */
    public function reorder($orderData) {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                UPDATE categoria SET orden = :orden
                WHERE id_categoria = :id
            ");
            
            foreach ($orderData as $id => $order) {
                $stmt->execute([
                    ':id' => $id,
                    ':orden' => $order
                ]);
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Categorías reordenadas correctamente'
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            $this->logError('Error en reorder: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al reordenar las categorías: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cambia el estado de una categoría (activar/desactivar)
     * 
     * @param int $id ID de la categoría
     * @param int $estado Nuevo estado (1=activo, 0=inactivo)
     * @return array Resultado de la operación
     */
    public function changeState($id, $estado) {
        try {
            $stmt = $this->db->prepare("
                UPDATE categoria 
                SET estado = :estado, fecha_actualizacion = NOW()
                WHERE id_categoria = :id
            ");
            
            $stmt->execute([
                ':id' => $id,
                ':estado' => $estado ? 1 : 0
            ]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Estado de la categoría actualizado correctamente'
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en changeState: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado de la categoría: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cambia el estado destacado de una categoría
     * 
     * @param int $id ID de la categoría
     * @param int $destacada Nuevo estado destacado (1=destacada, 0=no destacada)
     * @return array Resultado de la operación
     */
    public function setFeatured($id, $destacada) {
        try {
            $stmt = $this->db->prepare("
                UPDATE categoria 
                SET destacada = :destacada, fecha_actualizacion = NOW()
                WHERE id_categoria = :id
            ");
            
            $stmt->execute([
                ':id' => $id,
                ':destacada' => $destacada ? 1 : 0
            ]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Estado destacado de la categoría actualizado correctamente'
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en setFeatured: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado destacado de la categoría: ' . $e->getMessage()
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
     * Obtiene todos los productos de una categoría
     * 
     * @param int $categoryId ID de la categoría
     * @param int $limit Límite de resultados
     * @param int $offset Desplazamiento
     * @param array $filters Filtros adicionales
     * @return array Productos de la categoría
     */
    public function getProducts($categoryId, $limit = 12, $offset = 0, $filters = []) {
        try {
            // Verificar si la categoría existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM categoria WHERE id_categoria = :id");
            $checkStmt->execute([':id' => $categoryId]);
            
            if ($checkStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Categoría no encontrada',
                    'productos' => []
                ];
            }
            
            // Construir consulta base
            $sql = "
                SELECT p.id_producto, p.codigo, p.nombre, p.descripcion, p.precio_base,
                       p.estado, p.destacado, p.fecha_creacion, p.fecha_actualizacion,
                       sc.id_subcategoria, sc.nombre as subcategoria_nombre,
                       (SELECT ruta FROM producto_imagen WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as imagen,
                       (SELECT SUM(cantidad) FROM inventario WHERE id_producto = p.id_producto) as stock_total
                FROM producto p
                LEFT JOIN subcategoria sc ON p.id_subcategoria = sc.id_subcategoria
                WHERE p.id_categoria = :id_categoria
            ";
            
            $params = [':id_categoria' => $categoryId];
            
            // Agregar filtros adicionales
            if (!empty($filters)) {
                // Filtro por subcategoría
                if (isset($filters['subcategoria'])) {
                    $sql .= " AND p.id_subcategoria = :subcategoria";
                    $params[':subcategoria'] = $filters['subcategoria'];
                }
                
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
                        $sql = str_replace('LEFT JOIN subcategoria sc', 'LEFT JOIN subcategoria sc
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
                    'subcategoria' => [
                        'id' => $row['id_subcategoria'],
                        'nombre' => $row['subcategoria_nombre']
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
                'message' => 'Error al obtener productos de la categoría: ' . $e->getMessage(),
                'productos' => []
            ];
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
        
        $logFile = $logDir . '/category_model_' . date('Y-m-d') . '.log';
        $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}