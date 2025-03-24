<?php
/**
 * Modelo de Producto
 * Gestiona todas las operaciones relacionadas con productos
 */
class product {
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
     * Obtiene todos los productos con paginación
     * 
     * @param int $limit Límite de resultados
     * @param int $offset Desplazamiento
     * @param array $filters Filtros opcionales (categoría, estado, etc.)
     * @return array Productos y meta información
     */
    public function getAll($limit = 10, $offset = 0, $filters = []) {
        try {
            // Construir la consulta base
            $sql = "
                SELECT p.id_producto, p.codigo, p.nombre, p.descripcion, p.precio_base,
                       p.estado, p.destacado, p.fecha_creacion, p.fecha_actualizacion,
                       c.id_categoria, c.nombre as categoria_nombre,
                       sc.id_subcategoria, sc.nombre as subcategoria_nombre,
                       (SELECT SUM(i.cantidad) FROM inventario i WHERE i.id_producto = p.id_producto) as stock_total
                FROM producto p
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                LEFT JOIN subcategoria sc ON p.id_subcategoria = sc.id_subcategoria
            ";
            
            $where = [];
            $params = [];
            
            // Aplicar filtros
            if (!empty($filters)) {
                // Filtro por categoría
                if (isset($filters['categoria'])) {
                    $where[] = "p.id_categoria = :categoria";
                    $params[':categoria'] = $filters['categoria'];
                }
                
                // Filtro por subcategoría
                if (isset($filters['subcategoria'])) {
                    $where[] = "p.id_subcategoria = :subcategoria";
                    $params[':subcategoria'] = $filters['subcategoria'];
                }
                
                // Filtro por estado
                if (isset($filters['estado'])) {
                    $where[] = "p.estado = :estado";
                    $params[':estado'] = $filters['estado'];
                }
                
                // Filtro por productos destacados
                if (isset($filters['destacado'])) {
                    $where[] = "p.destacado = :destacado";
                    $params[':destacado'] = $filters['destacado'];
                }
                
                // Búsqueda por nombre o descripción
                if (isset($filters['busqueda'])) {
                    $where[] = "(p.nombre LIKE :busqueda OR p.descripcion LIKE :busqueda)";
                    $params[':busqueda'] = '%' . $filters['busqueda'] . '%';
                }
                
                // Filtro por precio mínimo
                if (isset($filters['precio_min'])) {
                    $where[] = "p.precio_base >= :precio_min";
                    $params[':precio_min'] = $filters['precio_min'];
                }
                
                // Filtro por precio máximo
                if (isset($filters['precio_max'])) {
                    $where[] = "p.precio_base <= :precio_max";
                    $params[':precio_max'] = $filters['precio_max'];
                }
                
                // Filtro por stock disponible
                if (isset($filters['con_stock'])) {
                    $sql = str_replace('FROM producto p', 'FROM producto p
                    LEFT JOIN inventario i ON p.id_producto = i.id_producto', $sql);
                    $where[] = "i.cantidad > 0";
                }
            }
            
            // Aplicar condiciones WHERE si existen
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            // Aplicar ordenamiento
            $orderBy = "p.id_producto DESC"; // Valor por defecto
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
                    case 'mas_reciente':
                        $orderBy = "p.fecha_creacion DESC";
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
            
            // Aplicar paginación
            $sql .= " LIMIT :limit OFFSET :offset";
            
            // Consulta para contar el total
            $countSql = "SELECT COUNT(*) as total FROM producto p";
            
            // Aplicar los mismos filtros a la consulta de conteo
            if (!empty($where)) {
                $countSql .= " WHERE " . implode(' AND ', $where);
            }
            
            // Ejecutar consulta de conteo
            $countStmt = $this->db->prepare($countSql);
            
            // Bindear parámetros para el conteo
            foreach ($params as $key => $value) {
                if ($key !== ':limit' && $key !== ':offset') {
                    $countStmt->bindValue($key, $value);
                }
            }
            
            $countStmt->execute();
            $totalProducts = $countStmt->fetchColumn();
            
            // Calcular número de páginas
            $totalPages = ceil($totalProducts / $limit);
            
            // Ejecutar consulta principal
            $stmt = $this->db->prepare($sql);
            
            // Bindear parámetros de limit y offset como enteros
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            // Bindear los demás parámetros
            foreach ($params as $key => $value) {
                if ($key !== ':limit' && $key !== ':offset') {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            
            // Obtener productos
            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Agregar información de imágenes
                $imagesSql = "SELECT id_producto_imagen, ruta, es_principal FROM producto_imagen WHERE id_producto = :id_producto";
                $imagesStmt = $this->db->prepare($imagesSql);
                $imagesStmt->execute([':id_producto' => $row['id_producto']]);
                $images = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Preparar datos del producto
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
                    'subcategoria' => [
                        'id' => $row['id_subcategoria'],
                        'nombre' => $row['subcategoria_nombre']
                    ],
                    'stock_total' => $row['stock_total'],
                    'imagenes' => $images
                ];
            }
            
            return [
                'success' => true,
                'productos' => $products,
                'total' => $totalProducts,
                'paginas' => $totalPages
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getAll: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage(),
                'productos' => []
            ];
        }
    }
    
    /**
     * Obtiene un producto por su ID
     * 
     * @param int $id ID del producto
     * @return array Datos del producto
     */
    public function getById($id) {
        try {
            // Consulta principal del producto
            $sql = "
                SELECT p.id_producto, p.codigo, p.nombre, p.descripcion, p.precio_base,
                       p.descripcion_larga, p.palabras_clave, 
                       p.estado, p.destacado, p.fecha_creacion, p.fecha_actualizacion,
                       c.id_categoria, c.nombre as categoria_nombre,
                       sc.id_subcategoria, sc.nombre as subcategoria_nombre
                FROM producto p
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                LEFT JOIN subcategoria sc ON p.id_subcategoria = sc.id_subcategoria
                WHERE p.id_producto = :id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ];
            }
            
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener imágenes del producto
            $imagesSql = "SELECT id_producto_imagen, ruta, es_principal FROM producto_imagen WHERE id_producto = :id_producto";
            $imagesStmt = $this->db->prepare($imagesSql);
            $imagesStmt->execute([':id_producto' => $id]);
            $images = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener detalles/atributos adicionales
            $detailsSql = "
                SELECT nombre_atributo, valor_atributo 
                FROM producto_detalle 
                WHERE id_producto = :id_producto
            ";
            $detailsStmt = $this->db->prepare($detailsSql);
            $detailsStmt->execute([':id_producto' => $id]);
            $details = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener stock disponible
            $stockSql = "
                SELECT SUM(cantidad) as stock_total 
                FROM inventario 
                WHERE id_producto = :id_producto
            ";
            $stockStmt = $this->db->prepare($stockSql);
            $stockStmt->execute([':id_producto' => $id]);
            $stock = $stockStmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener productos relacionados
            $relatedSql = "
                SELECT p.id_producto, p.nombre, p.precio_base,
                       (SELECT ruta FROM producto_imagen WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as imagen
                FROM producto p
                WHERE p.id_categoria = :categoria_id
                AND p.id_producto != :producto_id
                LIMIT 5
            ";
            $relatedStmt = $this->db->prepare($relatedSql);
            $relatedStmt->execute([
                ':categoria_id' => $product['id_categoria'],
                ':producto_id' => $id
            ]);
            $relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organizar datos del producto
            $formattedProduct = [
                'id' => $product['id_producto'],
                'codigo' => $product['codigo'],
                'nombre' => $product['nombre'],
                'descripcion' => $product['descripcion'],
                'descripcion_larga' => $product['descripcion_larga'],
                'palabras_clave' => $product['palabras_clave'],
                'precio_base' => $product['precio_base'],
                'estado' => $product['estado'],
                'destacado' => $product['destacado'],
                'fecha_creacion' => $product['fecha_creacion'],
                'fecha_actualizacion' => $product['fecha_actualizacion'],
                'categoria' => [
                    'id' => $product['id_categoria'],
                    'nombre' => $product['categoria_nombre']
                ],
                'subcategoria' => [
                    'id' => $product['id_subcategoria'],
                    'nombre' => $product['subcategoria_nombre']
                ],
                'imagenes' => $images,
                'detalles' => $details,
                'stock' => $stock['stock_total'] ?? 0,
                'productos_relacionados' => $relatedProducts
            ];
            
            // Incrementar contador de visitas
            $this->incrementViewCount($id);
            
            return [
                'success' => true,
                'producto' => $formattedProduct
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getById: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener el producto: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene productos destacados
     * 
     * @param int $limit Número de productos a obtener
     * @return array Lista de productos destacados
     */
    public function getFeatured($limit = 8) {
        try {
            $sql = "
                SELECT p.id_producto, p.nombre, p.precio_base, p.descripcion,
                       (SELECT ruta FROM producto_imagen WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as imagen
                FROM producto p
                WHERE p.destacado = 1
                ORDER BY p.fecha_actualizacion DESC
                LIMIT :limit
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[] = [
                    'id' => $row['id_producto'],
                    'nombre' => $row['nombre'],
                    'precio' => $row['precio_base'],
                    'descripcion' => $row['descripcion'],
                    'imagen' => $row['imagen']
                ];
            }
            
            return [
                'success' => true,
                'productos' => $products
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en getFeatured: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al obtener productos destacados: ' . $e->getMessage(),
                'productos' => []
            ];
        }
    }
    
    /**
     * Crea un nuevo producto
     * 
     * @param array $productData Datos del producto
     * @return array Resultado de la operación
     */
    public function create($productData) {
        try {
            // Validar datos
            if (empty($productData['nombre']) || empty($productData['codigo'])) {
                return [
                    'success' => false,
                    'message' => 'Nombre y código son campos requeridos'
                ];
            }
            
            // Verificar si el código ya existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM producto WHERE codigo = :codigo");
            $checkStmt->execute([':codigo' => $productData['codigo']]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'Ya existe un producto con ese código'
                ];
            }
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Insertar producto
            $stmt = $this->db->prepare("
                INSERT INTO producto (
                    codigo, nombre, descripcion, descripcion_larga, 
                    palabras_clave, precio_base, id_categoria, id_subcategoria,
                    estado, destacado, fecha_creacion, fecha_actualizacion
                ) VALUES (
                    :codigo, :nombre, :descripcion, :descripcion_larga,
                    :palabras_clave, :precio_base, :id_categoria, :id_subcategoria,
                    :estado, :destacado, NOW(), NOW()
                )
            ");
            
            $stmt->execute([
                ':codigo' => $productData['codigo'],
                ':nombre' => $productData['nombre'],
                ':descripcion' => $productData['descripcion'] ?? '',
                ':descripcion_larga' => $productData['descripcion_larga'] ?? '',
                ':palabras_clave' => $productData['palabras_clave'] ?? '',
                ':precio_base' => $productData['precio_base'] ?? 0,
                ':id_categoria' => $productData['id_categoria'] ?? null,
                ':id_subcategoria' => $productData['id_subcategoria'] ?? null,
                ':estado' => $productData['estado'] ?? 1, // 1 = activo por defecto
                ':destacado' => $productData['destacado'] ?? 0
            ]);
            
            // Obtener ID del producto insertado
            $productId = $this->db->lastInsertId();
            
            // Si hay detalles, insertarlos
            if (isset($productData['detalles']) && is_array($productData['detalles'])) {
                $detailStmt = $this->db->prepare("
                    INSERT INTO producto_detalle (
                        id_producto, nombre_atributo, valor_atributo
                    ) VALUES (
                        :id_producto, :nombre_atributo, :valor_atributo
                    )
                ");
                
                foreach ($productData['detalles'] as $detail) {
                    if (isset($detail['nombre']) && isset($detail['valor'])) {
                        $detailStmt->execute([
                            ':id_producto' => $productId,
                            ':nombre_atributo' => $detail['nombre'],
                            ':valor_atributo' => $detail['valor']
                        ]);
                    }
                }
            }
            
            // Si hay imágenes, procesarlas e insertarlas
            if (isset($productData['imagenes']) && is_array($productData['imagenes'])) {
                $imagenStmt = $this->db->prepare("
                    INSERT INTO producto_imagen (
                        id_producto, ruta, es_principal, orden
                    ) VALUES (
                        :id_producto, :ruta, :es_principal, :orden
                    )
                ");
                
                $orden = 1;
                foreach ($productData['imagenes'] as $imagen) {
                    $esPrincipal = ($orden == 1) ? 1 : 0;
                    if (isset($imagen['ruta'])) {
                        $imagenStmt->execute([
                            ':id_producto' => $productId,
                            ':ruta' => $imagen['ruta'],
                            ':es_principal' => $imagen['es_principal'] ?? $esPrincipal,
                            ':orden' => $orden++
                        ]);
                    }
                }
            }
            
            // Si hay inventario inicial, agregarlo
            if (isset($productData['stock_inicial']) && is_numeric($productData['stock_inicial'])) {
                $stockStmt = $this->db->prepare("
                    INSERT INTO inventario (
                        id_producto, id_almacen, cantidad, fecha_actualizacion
                    ) VALUES (
                        :id_producto, :id_almacen, :cantidad, NOW()
                    )
                ");
                
                $stockStmt->execute([
                    ':id_producto' => $productId,
                    ':id_almacen' => $productData['id_almacen'] ?? 1, // Almacén principal por defecto
                    ':cantidad' => $productData['stock_inicial']
                ]);
                
                // Registro histórico
                $historyStmt = $this->db->prepare("
                    INSERT INTO historico_stock (
                        id_producto, id_almacen, cantidad_anterior, cantidad_nueva,
                        tipo_movimiento, descripcion, fecha, id_usuario
                    ) VALUES (
                        :id_producto, :id_almacen, 0, :cantidad_nueva,
                        'ENTRADA', 'Stock inicial', NOW(), :id_usuario
                    )
                ");
                
                $historyStmt->execute([
                    ':id_producto' => $productId,
                    ':id_almacen' => $productData['id_almacen'] ?? 1,
                    ':cantidad_nueva' => $productData['stock_inicial'],
                    ':id_usuario' => $productData['id_usuario'] ?? 1
                ]);
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Producto creado correctamente',
                'id' => $productId
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            $this->logError('Error en create: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al crear el producto: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualiza un producto existente
     * 
     * @param int $id ID del producto
     * @param array $productData Datos del producto
     * @return array Resultado de la operación
     */
    public function update($id, $productData) {
        try {
            // Verificar si el producto existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM producto WHERE id_producto = :id");
            $checkStmt->execute([':id' => $id]);
            
            if ($checkStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ];
            }
            
            // Si se proporciona código, verificar que no exista en otro producto
            if (isset($productData['codigo'])) {
                $checkCodeStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM producto 
                    WHERE codigo = :codigo AND id_producto != :id
                ");
                
                $checkCodeStmt->execute([
                    ':codigo' => $productData['codigo'],
                    ':id' => $id
                ]);
                
                if ($checkCodeStmt->fetchColumn() > 0) {
                    return [
                        'success' => false,
                        'message' => 'Ya existe otro producto con ese código'
                    ];
                }
            }
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Preparar campos a actualizar
            $updateFields = [];
            $params = [':id' => $id];
            
            $possibleFields = [
                'codigo', 'nombre', 'descripcion', 'descripcion_larga',
                'palabras_clave', 'precio_base', 'id_categoria', 'id_subcategoria',
                'estado', 'destacado'
            ];
            
            foreach ($possibleFields as $field) {
                if (isset($productData[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[":$field"] = $productData[$field];
                }
            }
            
            // Agregar fecha de actualización
            $updateFields[] = "fecha_actualizacion = NOW()";
            
            // Si hay campos para actualizar
            if (!empty($updateFields)) {
                $sql = "UPDATE producto SET " . implode(', ', $updateFields) . " WHERE id_producto = :id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
            
            // Actualizar detalles si se proporcionan
            if (isset($productData['detalles']) && is_array($productData['detalles'])) {
                // Eliminar detalles existentes
                $deleteStmt = $this->db->prepare("DELETE FROM producto_detalle WHERE id_producto = :id");
                $deleteStmt->execute([':id' => $id]);
                
                // Insertar nuevos detalles
                $detailStmt = $this->db->prepare("
                    INSERT INTO producto_detalle (
                        id_producto, nombre_atributo, valor_atributo
                    ) VALUES (
                        :id_producto, :nombre_atributo, :valor_atributo
                    )
                ");
                
                foreach ($productData['detalles'] as $detail) {
                    if (isset($detail['nombre']) && isset($detail['valor'])) {
                        $detailStmt->execute([
                            ':id_producto' => $id,
                            ':nombre_atributo' => $detail['nombre'],
                            ':valor_atributo' => $detail['valor']
                        ]);
                    }
                }
            }
            
            // Si hay nuevas imágenes, procesarlas
            if (isset($productData['nuevas_imagenes']) && is_array($productData['nuevas_imagenes'])) {
                // Obtener orden actual más alto
                $orderStmt = $this->db->prepare("
                    SELECT MAX(orden) FROM producto_imagen WHERE id_producto = :id
                ");
                $orderStmt->execute([':id' => $id]);
                $orden = $orderStmt->fetchColumn() ?: 0;
                
                $imagenStmt = $this->db->prepare("
                    INSERT INTO producto_imagen (
                        id_producto, ruta, es_principal, orden
                    ) VALUES (
                        :id_producto, :ruta, :es_principal, :orden
                    )
                ");
                
                foreach ($productData['nuevas_imagenes'] as $imagen) {
                    $orden++;
                    if (isset($imagen['ruta'])) {
                        $imagenStmt->execute([
                            ':id_producto' => $id,
                            ':ruta' => $imagen['ruta'],
                            ':es_principal' => $imagen['es_principal'] ?? 0,
                            ':orden' => $orden
                        ]);
                    }
                }
            }
            
            // Si se especifica una imagen principal
            if (isset($productData['id_imagen_principal'])) {
                // Resetear todas las imágenes a no principales
                $resetStmt = $this->db->prepare("
                    UPDATE producto_imagen SET es_principal = 0 
                    WHERE id_producto = :id
                ");
                $resetStmt->execute([':id' => $id]);
                
                // Establecer la imagen seleccionada como principal
                $setPrincipalStmt = $this->db->prepare("
                    UPDATE producto_imagen SET es_principal = 1 
                    WHERE id_producto_imagen = :id_imagen AND id_producto = :id_producto
                ");
                $setPrincipalStmt->execute([
                    ':id_imagen' => $productData['id_imagen_principal'],
                    ':id_producto' => $id
                ]);
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Producto actualizado correctamente'
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            $this->logError('Error en update: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al actualizar el producto: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Elimina un producto
     * 
     * @param int $id ID del producto
     * @return array Resultado de la operación
     */
    public function delete($id) {
        try {
            // Verificar si el producto existe
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM producto WHERE id_producto = :id");
            $checkStmt->execute([':id' => $id]);
            
            if ($checkStmt->fetchColumn() == 0) {
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ];
            }
            
            // Verificar si el producto está en algún pedido
            $orderCheckStmt = $this->db->prepare("
                SELECT COUNT(*) FROM pedido_item WHERE id_producto = :id
            ");
            $orderCheckStmt->execute([':id' => $id]);
            
            if ($orderCheckStmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar el producto porque está asociado a uno o más pedidos'
                ];
            }
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Eliminar detalles del producto
            $deleteDetailsStmt = $this->db->prepare("DELETE FROM producto_detalle WHERE id_producto = :id");
            $deleteDetailsStmt->execute([':id' => $id]);
            
            // Eliminar imágenes del producto
            $deleteImagesStmt = $this->db->prepare("DELETE FROM producto_imagen WHERE id_producto = :id");
            $deleteImagesStmt->execute([':id' => $id]);
            
            // Eliminar inventario del producto
            $deleteInventoryStmt = $this->db->prepare("DELETE FROM inventario WHERE id_producto = :id");
            $deleteInventoryStmt->execute([':id' => $id]);
            
            // Eliminar el producto
            $deleteProductStmt = $this->db->prepare("DELETE FROM producto WHERE id_producto = :id");
            $deleteProductStmt->execute([':id' => $id]);
            
            // Confirmar transacción
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Producto eliminado correctamente'
            ];
            
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            
            $this->logError('Error en delete: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al eliminar el producto: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Incrementa el contador de vistas de un producto
     * 
     * @param int $id ID del producto
     * @return bool True si se incrementó correctamente
     */
    private function incrementViewCount($id) {
        try {
            // Verificar si existe la tabla de vistas de producto
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS vista_producto (
                    id_producto INT NOT NULL,
                    contador INT DEFAULT 0,
                    ultima_vista DATETIME,
                    PRIMARY KEY (id_producto)
                )
            ");
            
            // Intentar actualizar el contador si existe
            $updateStmt = $this->db->prepare("
                UPDATE vista_producto 
                SET contador = contador + 1, ultima_vista = NOW() 
                WHERE id_producto = :id
            ");
            
            $updateStmt->execute([':id' => $id]);
            
            // Si no se actualizó ninguna fila, es porque no existe el registro
            if ($updateStmt->rowCount() == 0) {
                // Insertar nuevo registro
                $insertStmt = $this->db->prepare("
                    INSERT INTO vista_producto (id_producto, contador, ultima_vista)
                    VALUES (:id, 1, NOW())
                ");
                
                $insertStmt->execute([':id' => $id]);
            }
            
            return true;
            
        } catch (PDOException $e) {
            $this->logError('Error en incrementViewCount: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca productos por texto
     * 
     * @param string $query Texto a buscar
     * @param int $limit Límite de resultados
     * @return array Resultados de la búsqueda
     */
    public function search($query, $limit = 10) {
        try {
            $sql = "
                SELECT p.id_producto, p.nombre, p.descripcion, p.precio_base,
                       c.nombre as categoria_nombre,
                       (SELECT ruta FROM producto_imagen WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as imagen
                FROM producto p
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                WHERE (
                    p.nombre LIKE :query 
                    OR p.descripcion LIKE :query 
                    OR p.palabras_clave LIKE :query
                    OR c.nombre LIKE :query
                )
                AND p.estado = 1
                ORDER BY 
                    CASE WHEN p.nombre LIKE :exact_query THEN 1
                         WHEN p.nombre LIKE :start_query THEN 2
                         ELSE 3
                    END,
                    p.destacado DESC,
                    (SELECT contador FROM vista_producto WHERE id_producto = p.id_producto) DESC,
                    p.fecha_actualizacion DESC
                LIMIT :limit
            ";
            
            $stmt = $this->db->prepare($sql);
            
            $searchParam = '%' . $query . '%';
            $exactParam = $query;
            $startParam = $query . '%';
            
            $stmt->bindValue(':query', $searchParam);
            $stmt->bindValue(':exact_query', $exactParam);
            $stmt->bindValue(':start_query', $startParam);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = [
                    'id' => $row['id_producto'],
                    'nombre' => $row['nombre'],
                    'descripcion' => $row['descripcion'],
                    'precio' => $row['precio_base'],
                    'categoria' => $row['categoria_nombre'],
                    'imagen' => $row['imagen']
                ];
            }
            
            // Registrar la búsqueda si hay resultados
            if (!empty($results)) {
                $this->logSearch($query, count($results));
            }
            
            return [
                'success' => true,
                'resultados' => $results,
                'total' => count($results)
            ];
            
        } catch (PDOException $e) {
            $this->logError('Error en search: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al buscar productos: ' . $e->getMessage(),
                'resultados' => []
            ];
        }
    }
    
    /**
     * Registra un término de búsqueda
     * 
     * @param string $query Término de búsqueda
     * @param int $results Número de resultados
     * @return bool True si se registró correctamente
     */
    private function logSearch($query, $results) {
        try {
            // Crear tabla si no existe
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS busqueda (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    termino VARCHAR(255) NOT NULL,
                    num_resultados INT DEFAULT 0,
                    fecha DATETIME,
                    ip VARCHAR(45),
                    id_usuario INT NULL,
                    INDEX (termino),
                    INDEX (fecha)
                )
            ");
            
            $stmt = $this->db->prepare("
                INSERT INTO busqueda (termino, num_resultados, fecha, ip, id_usuario)
                VALUES (:termino, :num_resultados, NOW(), :ip, :id_usuario)
            ");
            
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            $stmt->execute([
                ':termino' => $query,
                ':num_resultados' => $results,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ':id_usuario' => $userId
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            $this->logError('Error en logSearch: ' . $e->getMessage());
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
        
        $logFile = $logDir . '/product_model_' . date('Y-m-d') . '.log';
        $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}