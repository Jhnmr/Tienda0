-- Creación de la base de datos
CREATE DATABASE Tienda0;
USE Tienda0;

-- Configuración de caracteres
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- GESTIÓN DE IDIOMAS
CREATE TABLE IDIOMA (
    id_idioma INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(10) NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    por_defecto BOOLEAN NOT NULL DEFAULT FALSE,
    UNIQUE KEY uk_idioma_codigo (codigo)
);

CREATE TABLE TRADUCCION (
    id_traduccion INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(255) NOT NULL,
    id_idioma INT NOT NULL,
    valor TEXT NOT NULL,
    seccion VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_idioma) REFERENCES IDIOMA(id_idioma)
);

-- PAÍSES
CREATE TABLE PAIS (
    id_pais INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(5) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    UNIQUE KEY uk_pais_codigo (codigo)
);

-- MONEDAS
CREATE TABLE MONEDA (
    id_moneda INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(5) NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    simbolo VARCHAR(5) NOT NULL,
    activa BOOLEAN NOT NULL DEFAULT TRUE,
    por_defecto BOOLEAN NOT NULL DEFAULT FALSE,
    UNIQUE KEY uk_moneda_codigo (codigo)
);

-- GESTIÓN DE USUARIOS
CREATE TABLE SEGMENTO_CLIENTE (
    id_segmento INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    criterios TEXT
);

CREATE TABLE USUARIO (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    verificado BOOLEAN NOT NULL DEFAULT FALSE,
    id_idioma_preferido INT,
    id_segmento_cliente INT,
    UNIQUE KEY uk_usuario_email (email),
    FOREIGN KEY (id_idioma_preferido) REFERENCES IDIOMA(id_idioma),
    FOREIGN KEY (id_segmento_cliente) REFERENCES SEGMENTO_CLIENTE(id_segmento)
);

CREATE TABLE USUARIO_PERFIL (
    id_perfil INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    nombres VARCHAR(100),
    apellidos VARCHAR(100),
    telefono VARCHAR(20),
    fecha_nacimiento DATE,
    genero VARCHAR(20),
    marketing_consent BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    UNIQUE KEY uk_usuario_perfil (id_usuario)
);

CREATE TABLE DIRECCION (
    id_direccion INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    nombre_contacto VARCHAR(100) NOT NULL,
    direccion_linea1 VARCHAR(255) NOT NULL,
    direccion_linea2 VARCHAR(255),
    ciudad VARCHAR(100) NOT NULL,
    estado_provincia VARCHAR(100) NOT NULL,
    codigo_postal VARCHAR(20) NOT NULL,
    id_pais INT NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    principal BOOLEAN NOT NULL DEFAULT FALSE,
    tipo VARCHAR(20) NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    FOREIGN KEY (id_pais) REFERENCES PAIS(id_pais)
);

CREATE TABLE METODO_PAGO (
    id_metodo_pago INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    proveedor VARCHAR(50) NOT NULL,
    numero_enmascarado VARCHAR(50) NOT NULL,
    fecha_vencimiento DATE,
    principal BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
);

CREATE TABLE ROL (
    id_rol INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    UNIQUE KEY uk_rol_nombre (nombre)
);

CREATE TABLE USUARIO_ROL (
    id_usuario_rol INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_rol INT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    FOREIGN KEY (id_rol) REFERENCES ROL(id_rol),
    UNIQUE KEY uk_usuario_rol (id_usuario, id_rol)
);

CREATE TABLE PERMISO (
    id_permiso INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    descripcion TEXT,
    modulo VARCHAR(50) NOT NULL,
    UNIQUE KEY uk_permiso_codigo (codigo)
);

CREATE TABLE ROL_PERMISO (
    id_rol_permiso INT PRIMARY KEY AUTO_INCREMENT,
    id_rol INT NOT NULL,
    id_permiso INT NOT NULL,
    FOREIGN KEY (id_rol) REFERENCES ROL(id_rol),
    FOREIGN KEY (id_permiso) REFERENCES PERMISO(id_permiso),
    UNIQUE KEY uk_rol_permiso (id_rol, id_permiso)
);

CREATE TABLE AUTENTICACION_EXTERNA (
    id_autenticacion INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    proveedor VARCHAR(50) NOT NULL,
    id_externo VARCHAR(255) NOT NULL,
    fecha_vinculacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    UNIQUE KEY uk_autenticacion_externa (proveedor, id_externo)
);

CREATE TABLE HISTORIAL_ACCESO (
    id_acceso INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    fecha_acceso TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_acceso VARCHAR(50) NOT NULL,
    user_agent TEXT,
    dispositivo VARCHAR(100),
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
);

CREATE TABLE RECUPERACION_PASSWORD (
    id_recuperacion INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    fecha_solicitud TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion TIMESTAMP NOT NULL,
    usado BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    UNIQUE KEY uk_recuperacion_token (token)
);

-- GESTIÓN DE PRODUCTOS
CREATE TABLE CATEGORIA (
    id_categoria INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_categoria_nombre (nombre)
);

CREATE TABLE SUBCATEGORIA (
    id_subcategoria INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    id_categoria INT NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_categoria) REFERENCES CATEGORIA(id_categoria),
    UNIQUE KEY uk_subcategoria_nombre_categoria (nombre, id_categoria)
);

CREATE TABLE TIPO_PRODUCTO (
    id_tipo_producto INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    esquema_atributos TEXT,
    tipo_negocio VARCHAR(50),
    UNIQUE KEY uk_tipo_producto_nombre (nombre)
);

CREATE TABLE ATRIBUTO_PRODUCTO (
    id_atributo INT PRIMARY KEY AUTO_INCREMENT,
    id_tipo_producto INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    tipo_dato VARCHAR(50) NOT NULL,
    requerido BOOLEAN NOT NULL DEFAULT FALSE,
    filtrable BOOLEAN NOT NULL DEFAULT FALSE,
    comparable BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (id_tipo_producto) REFERENCES TIPO_PRODUCTO(id_tipo_producto),
    UNIQUE KEY uk_atributo_nombre_tipo (nombre, id_tipo_producto)
);

CREATE TABLE PRODUCTO (
    id_producto INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(50) NOT NULL,
    id_categoria INT NOT NULL,
    id_subcategoria INT,
    id_tipo_producto INT NOT NULL,
    precio_base DECIMAL(10,2) NOT NULL,
    destacado BOOLEAN NOT NULL DEFAULT FALSE,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_categoria) REFERENCES CATEGORIA(id_categoria),
    FOREIGN KEY (id_subcategoria) REFERENCES SUBCATEGORIA(id_subcategoria),
    FOREIGN KEY (id_tipo_producto) REFERENCES TIPO_PRODUCTO(id_tipo_producto),
    UNIQUE KEY uk_producto_sku (sku)
);

CREATE TABLE VALOR_ATRIBUTO (
    id_valor_atributo INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_atributo INT NOT NULL,
    valor TEXT NOT NULL,
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
    FOREIGN KEY (id_atributo) REFERENCES ATRIBUTO_PRODUCTO(id_atributo),
    UNIQUE KEY uk_valor_atributo (id_producto, id_atributo)
);

CREATE TABLE PRODUCTO_DETALLE (
    id_producto_detalle INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_idioma INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    especificaciones TEXT,
    seo_titulo VARCHAR(255),
    seo_descripcion TEXT,
    seo_keywords VARCHAR(255),
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
    FOREIGN KEY (id_idioma) REFERENCES IDIOMA(id_idioma),
    UNIQUE KEY uk_producto_detalle (id_producto, id_idioma)
);

CREATE TABLE PRODUCTO_IMAGEN (
    id_imagen INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    url_imagen VARCHAR(255) NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    principal BOOLEAN NOT NULL DEFAULT FALSE,
    alt_texto VARCHAR(255),
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
);

CREATE TABLE ALMACEN (
    id_almacen INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    ubicacion VARCHAR(255) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE KEY uk_almacen_nombre (nombre)
);

CREATE TABLE INVENTARIO (
    id_inventario INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_almacen INT NOT NULL,
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    ultima_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
    FOREIGN KEY (id_almacen) REFERENCES ALMACEN(id_almacen),
    UNIQUE KEY uk_inventario (id_producto, id_almacen)
);

CREATE TABLE HISTORICO_STOCK (
    id_historico INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_almacen INT NOT NULL,
    cantidad_anterior INT NOT NULL,
    cantidad_nueva INT NOT NULL,
    tipo_movimiento VARCHAR(50) NOT NULL,
    fecha_movimiento TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario_operador INT,
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
    FOREIGN KEY (id_almacen) REFERENCES ALMACEN(id_almacen),
    FOREIGN KEY (id_usuario_operador) REFERENCES USUARIO(id_usuario)
);

CREATE TABLE PRECIO (
    id_precio INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_moneda INT NOT NULL,
    precio_venta DECIMAL(10,2) NOT NULL,
    precio_oferta DECIMAL(10,2),
    fecha_inicio_oferta TIMESTAMP NULL,
    fecha_fin_oferta TIMESTAMP NULL,
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
    FOREIGN KEY (id_moneda) REFERENCES MONEDA(id_moneda),
    UNIQUE KEY uk_precio (id_producto, id_moneda)
);

CREATE TABLE HISTORICO_PRECIO (
    id_historico_precio INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_moneda INT NOT NULL,
    precio_anterior DECIMAL(10,2) NOT NULL,
    precio_nuevo DECIMAL(10,2) NOT NULL,
    fecha_cambio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
    FOREIGN KEY (id_moneda) REFERENCES MONEDA(id_moneda)
);

CREATE TABLE IMPUESTO (
    id_impuesto INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    porcentaje DECIMAL(5,2) NOT NULL,
    codigo_fiscal VARCHAR(50) NOT NULL,
    UNIQUE KEY uk_impuesto_codigo (codigo_fiscal)
);

CREATE TABLE PRODUCTO_IMPUESTO (
    id_producto_impuesto INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_impuesto INT NOT NULL,
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
    FOREIGN KEY (id_impuesto) REFERENCES IMPUESTO(id_impuesto),
    UNIQUE KEY uk_producto_impuesto (id_producto, id_impuesto)
);

-- CARRITO Y PEDIDOS
CREATE TABLE CARRITO (
    id_carrito INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultima_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
);

CREATE TABLE CARRITO_ITEM (
    id_carrito_item INT PRIMARY KEY AUTO_INCREMENT,
    id_carrito INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_carrito) REFERENCES CARRITO(id_carrito),
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
    UNIQUE KEY uk_carrito_item (id_carrito, id_producto)
);

CREATE TABLE CARRITO_ABANDONADO (
    id_carrito_abandonado INT PRIMARY KEY AUTO_INCREMENT,
    id_carrito INT NOT NULL,
    notificacion_enviada BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_ultima_notificacion TIMESTAMP NULL,
    FOREIGN KEY (id_carrito) REFERENCES CARRITO(id_carrito),
    UNIQUE KEY uk_carrito_abandonado (id_carrito)
);

CREATE TABLE LISTA_DESEOS (
    id_lista_deseos INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    publica BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
);

CREATE TABLE LISTA_DESEOS_ITEM (
    id_lista_item INT PRIMARY KEY AUTO_INCREMENT,
    id_lista_deseos INT NOT NULL,
    id_producto INT NOT NULL,
    fecha_agregado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_lista_deseos) REFERENCES LISTA_DESEOS(id_lista_deseos),
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
    UNIQUE KEY uk_lista_deseos_item (id_lista_deseos, id_producto)
);

CREATE TABLE SERVICIO_ENVIO (
    id_servicio_envio INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(50) NOT NULL,
    costo_base DECIMAL(10,2) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE KEY uk_servicio_envio_codigo (codigo)
);

CREATE TABLE PEDIDO (
    id_pedido INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    estado_pedido VARCHAR(50) NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL,
    impuestos DECIMAL(10,2) NOT NULL,
    costo_envio DECIMAL(10,2) NOT NULL,
    descuentos DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    id_direccion_facturacion INT NOT NULL,
    id_direccion_envio INT NOT NULL,
    id_metodo_pago INT NOT NULL,
    id_moneda INT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    FOREIGN KEY (id_direccion_facturacion) REFERENCES DIRECCION(id_direccion),
    FOREIGN KEY (id_direccion_envio) REFERENCES DIRECCION(id_direccion),
    FOREIGN KEY (id_metodo_pago) REFERENCES METODO_PAGO(id_metodo_pago),
    FOREIGN KEY (id_moneda) REFERENCES MONEDA(id_moneda)
);

CREATE TABLE PEDIDO_ESTADO_HISTORIAL (
    id_historial INT PRIMARY KEY AUTO_INCREMENT,
    id_pedido INT NOT NULL,
    estado_anterior VARCHAR(50) NOT NULL,
    estado_nuevo VARCHAR(50) NOT NULL,
    fecha_cambio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario_operador INT,
    notas TEXT,
    FOREIGN KEY (id_pedido) REFERENCES PEDIDO(id_pedido),
    FOREIGN KEY (id_usuario_operador) REFERENCES USUARIO(id_usuario)
);

CREATE TABLE PEDIDO_ITEM (
    id_pedido_item INT PRIMARY KEY AUTO_INCREMENT,
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    impuestos DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_pedido) REFERENCES PEDIDO(id_pedido),
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
);

CREATE TABLE ENVIO (
    id_envio INT PRIMARY KEY AUTO_INCREMENT,
    id_pedido INT NOT NULL,
    id_servicio_envio INT NOT NULL,
    estado_envio VARCHAR(50) NOT NULL,
    numero_seguimiento VARCHAR(100),
    fecha_envio TIMESTAMP NULL,
    fecha_estimada_entrega TIMESTAMP NULL,
    FOREIGN KEY (id_pedido) REFERENCES PEDIDO(id_pedido),
    FOREIGN KEY (id_servicio_envio) REFERENCES SERVICIO_ENVIO(id_servicio_envio),
    UNIQUE KEY uk_envio_pedido (id_pedido)
);

-- FACTURACIÓN
CREATE TABLE FACTURA (
    id_factura INT PRIMARY KEY AUTO_INCREMENT,
    id_pedido INT NOT NULL,
    numero_factura VARCHAR(50) NOT NULL,
    fecha_emision TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL,
    impuestos DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_pedido) REFERENCES PEDIDO(id_pedido),
    UNIQUE KEY uk_factura_numero (numero_factura),
    UNIQUE KEY uk_factura_pedido (id_pedido)
);

CREATE TABLE FACTURA_ITEM (
    id_factura_item INT PRIMARY KEY AUTO_INCREMENT,
    id_factura INT NOT NULL,
    id_producto INT NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    impuestos DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_factura) REFERENCES FACTURA(id_factura),
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
);

CREATE TABLE NOTA_CREDITO (
    id_nota_credito INT PRIMARY KEY AUTO_INCREMENT,
    id_factura INT NOT NULL,
    numero_nota VARCHAR(50) NOT NULL,
    fecha_emision TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    monto DECIMAL(10,2) NOT NULL,
    motivo TEXT NOT NULL,
    FOREIGN KEY (id_factura) REFERENCES FACTURA(id_factura),
    UNIQUE KEY uk_nota_credito_numero (numero_nota)
);

CREATE TABLE PAGO (
    id_pago INT PRIMARY KEY AUTO_INCREMENT,
    id_pedido INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    metodo VARCHAR(50) NOT NULL,
    referencia_externa VARCHAR(100),
    fecha_pago TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_pedido) REFERENCES PEDIDO(id_pedido)
);

-- MARKETING Y PROMOCIONES
CREATE TABLE PROMOCION (
    id_promocion INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(50),
    tipo VARCHAR(50) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    fecha_inicio TIMESTAMP NOT NULL,
    fecha_fin TIMESTAMP NOT NULL,
    acumulable BOOLEAN NOT NULL DEFAULT FALSE,
    usos_maximos INT,
    usos_actuales INT NOT NULL DEFAULT 0,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE KEY uk_promocion_codigo (codigo)
);

CREATE TABLE PROMOCION_CONDICION (
    id_condicion INT PRIMARY KEY AUTO_INCREMENT,
    id_promocion INT NOT NULL,
    tipo_condicion VARCHAR(50) NOT NULL,
    valor_condicion VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_promocion) REFERENCES PROMOCION(id_promocion)
);

CREATE TABLE PROMOCION_APLICACION (
    id_aplicacion INT PRIMARY KEY AUTO_INCREMENT,
    id_promocion INT NOT NULL,
    tipo_aplicacion VARCHAR(50) NOT NULL,
    id_elemento INT,
    FOREIGN KEY (id_promocion) REFERENCES PROMOCION(id_promocion)
);

CREATE TABLE CAMPANA_MARKETING (
    id_campana INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    tipo VARCHAR(50) NOT NULL,
    fecha_inicio TIMESTAMP NOT NULL,
    fecha_fin TIMESTAMP NOT NULL,
    activa BOOLEAN NOT NULL DEFAULT TRUE
);

-- RESEÑAS Y VALORACIONES
CREATE TABLE RESENA (
    id_resena INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_usuario INT NOT NULL,
    puntuacion INT NOT NULL,
    comentario TEXT,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verificada BOOLEAN NOT NULL DEFAULT FALSE,
    aprobada BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
);

-- ANÁLISIS DE DATOS Y SEGUIMIENTO
CREATE TABLE SESION_USUARIO (
    id_sesion INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    token_sesion VARCHAR(255) NOT NULL,
    inicio_sesion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fin_sesion TIMESTAMP NULL,
    ip_cliente VARCHAR(50) NOT NULL,
    dispositivo VARCHAR(100),
    origen_entrada VARCHAR(100),
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    UNIQUE KEY uk_sesion_token (token_sesion)
);

CREATE TABLE EVENTO_USUARIO (
    id_evento INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    tipo_evento VARCHAR(50) NOT NULL,
    entidad_afectada VARCHAR(50),
    id_entidad INT,
    datos_contexto TEXT,
    url VARCHAR(255),
    fecha_evento TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_cliente VARCHAR(50) NOT NULL,
    user_agent TEXT,
    id_sesion INT,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    FOREIGN KEY (id_sesion) REFERENCES SESION_USUARIO(id_sesion)
);

CREATE TABLE VISTA_PRODUCTO (
    id_vista INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_usuario INT,
    id_sesion INT,
    fecha_vista TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    duracion_segundos INT,
    origen VARCHAR(100),
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto),
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    FOREIGN KEY (id_sesion) REFERENCES SESION_USUARIO(id_sesion)
);

CREATE TABLE BUSQUEDA (
    id_busqueda INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT,
    id_sesion INT,
    termino_busqueda VARCHAR(255) NOT NULL,
    resultados_totales INT NOT NULL DEFAULT 0,
    fecha_busqueda TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    convertida BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    FOREIGN KEY (id_sesion) REFERENCES SESION_USUARIO(id_sesion)
);

CREATE TABLE INDICE_BUSQUEDA (
    id_indice INT PRIMARY KEY AUTO_INCREMENT,
    tipo_entidad VARCHAR(50) NOT NULL,
    id_entidad INT NOT NULL,
    texto_indexado TEXT NOT NULL,
    ultima_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE VISTA_MATERIALIZADA (
    id_vista INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    consulta_sql TEXT NOT NULL,
    programacion_actualizacion VARCHAR(50),
    ultima_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_vista_nombre (nombre)
);

-- RECOMENDACIONES
CREATE TABLE RECOMENDACION_ENGINE (
    id_engine INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    algoritmo VARCHAR(100) NOT NULL,
    parametros TEXT,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    ultima_ejecucion TIMESTAMP NULL,
    UNIQUE KEY uk_engine_nombre (nombre)
);

CREATE TABLE RECOMENDACION (
    id_recomendacion INT PRIMARY KEY AUTO_INCREMENT,
    tipo VARCHAR(50) NOT NULL,
    id_origen INT NOT NULL,
    id_destino INT NOT NULL,
    peso DECIMAL(5,2) NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_engine INT NOT NULL,
    FOREIGN KEY (id_engine) REFERENCES RECOMENDACION_ENGINE(id_engine)
);

-- NOTIFICACIONES
CREATE TABLE PLANTILLA_NOTIFICACION (
    id_plantilla INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    contenido_html TEXT NOT NULL,
    contenido_texto TEXT NOT NULL,
    UNIQUE KEY uk_plantilla_codigo (codigo)
);

CREATE TABLE NOTIFICACION (
    id_notificacion INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    leida BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    enlace VARCHAR(255),
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
);

-- CONFIGURACIÓN DE LA TIENDA
CREATE TABLE CONFIGURACION_TIENDA (
    id_configuracion INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(100) NOT NULL,
    valor TEXT NOT NULL,
    grupo VARCHAR(50) NOT NULL,
    UNIQUE KEY uk_configuracion_clave (clave)
);

-- TEMAS Y DISEÑOS
CREATE TABLE TEMA (
    id_tema INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    tipo_negocio VARCHAR(50),
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tema_nombre (nombre)
);

-- INTEGRACIÓN SISTEMAS EXTERNOS
CREATE TABLE INTEGRACION (
    id_integracion INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    credenciales TEXT,
    activa BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE KEY uk_integracion_nombre (nombre)
);

CREATE TABLE INTEGRACION_LOG (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    id_integracion INT NOT NULL,
    tipo_operacion VARCHAR(50) NOT NULL,
    fecha_operacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(50) NOT NULL,
    detalle_respuesta TEXT,
    error_mensaje TEXT,
    FOREIGN KEY (id_integracion) REFERENCES INTEGRACION(id_integracion)
);

-- API Y MICROSERVICIOS
CREATE TABLE MICROSERVICIO (
    id_microservicio INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    endpoint_base VARCHAR(255) NOT NULL,
    version VARCHAR(20) NOT NULL,
    estado VARCHAR(50) NOT NULL,
    ultimo_heartbeat TIMESTAMP NULL,
    UNIQUE KEY uk_microservicio_nombre (nombre)
);

CREATE TABLE API_KEY (
    id_api_key INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    id_usuario INT NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion TIMESTAMP NOT NULL,
    activa BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    UNIQUE KEY uk_api_key_clave (clave)
);

CREATE TABLE API_REQUEST_LOG (
    id_request INT PRIMARY KEY AUTO_INCREMENT,
    id_api_key INT NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    metodo VARCHAR(10) NOT NULL,
    fecha_request TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    codigo_respuesta INT NOT NULL,
    tiempo_respuesta_ms INT NOT NULL,
    FOREIGN KEY (id_api_key) REFERENCES API_KEY(id_api_key)
);

-- Reactivamos las llaves foráneas
SET FOREIGN_KEY_CHECKS = 1;