<?php
/**
 * Archivo de funciones de validación para la aplicación
 */

/**
 * Valida un email
 * 
 * @param string $email Email a validar
 * @return bool True si es válido, false si no
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida que un string tenga una longitud dentro de un rango
 * 
 * @param string $str String a validar
 * @param int $min Longitud mínima
 * @param int $max Longitud máxima
 * @return bool True si es válido, false si no
 */
function validateLength($str, $min, $max) {
    $length = mb_strlen($str, 'UTF-8');
    return $length >= $min && $length <= $max;
}

/**
 * Valida que un string contenga solo letras y espacios
 * 
 * @param string $str String a validar
 * @return bool True si es válido, false si no
 */
function validateName($str) {
    return preg_match('/^[a-záéíóúüñA-ZÁÉÍÓÚÜÑ\s]+$/u', $str);
}

/**
 * Valida que un string contenga una contraseña segura
 * - Al menos 8 caracteres
 * - Al menos una letra mayúscula
 * - Al menos una letra minúscula
 * - Al menos un número
 * - Al menos un carácter especial (@$!%*?&)
 * 
 * @param string $password Contraseña a validar
 * @return bool True si es válida, false si no
 */
function validatePassword($password) {
    // Mínimo 8 caracteres
    if (strlen($password) < 8) {
        return false;
    }
    
    // Al menos una letra mayúscula
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // Al menos una letra minúscula
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // Al menos un número
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    // Al menos un carácter especial
    if (!preg_match('/[@$!%*?&]/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * Valida un número de teléfono
 * 
 * @param string $phone Número de teléfono a validar
 * @return bool True si es válido, false si no
 */
function validatePhone($phone) {
    // Eliminamos espacios, guiones y paréntesis para la validación
    $phone = preg_replace('/\s+|-|\(|\)/', '', $phone);
    
    // Verificamos que sean solo dígitos y tenga entre 8 y 15 caracteres
    return preg_match('/^\d{8,15}$/', $phone);
}

/**
 * Valida una fecha en formato Y-m-d
 * 
 * @param string $date Fecha a validar
 * @return bool True si es válida, false si no
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Valida que un valor esté dentro de un arreglo de valores permitidos
 * 
 * @param mixed $value Valor a validar
 * @param array $allowedValues Valores permitidos
 * @return bool True si es válido, false si no
 */
function validateInArray($value, array $allowedValues) {
    return in_array($value, $allowedValues);
}

/**
 * Valida que un archivo subido sea una imagen válida
 * 
 * @param array $file Array de $_FILES para el archivo
 * @param int $maxSize Tamaño máximo en bytes (por defecto 2MB)
 * @param array $allowedTypes Tipos MIME permitidos
 * @return array Arreglo con 'valid' (bool) y 'message' (string) si hay error
 */
function validateImage($file, $maxSize = 2097152, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']) {
    $result = [
        'valid' => true,
        'message' => ''
    ];
    
    // Verificar si se subió un archivo
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        $result['valid'] = false;
        $result['message'] = 'No se ha subido ningún archivo';
        return $result;
    }
    
    // Verificar errores en la subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['valid'] = false;
        $result['message'] = 'Error al subir el archivo: ' . $file['error'];
        return $result;
    }
    
    // Verificar tamaño
    if ($file['size'] > $maxSize) {
        $result['valid'] = false;
        $result['message'] = 'El archivo es demasiado grande. Máximo ' . ($maxSize / 1024 / 1024) . 'MB';
        return $result;
    }
    
    // Verificar tipo MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime, $allowedTypes)) {
        $result['valid'] = false;
        $result['message'] = 'Tipo de archivo no permitido. Se permiten: ' . implode(', ', $allowedTypes);
        return $result;
    }
    
    // Verificar que realmente sea una imagen válida
    if (!getimagesize($file['tmp_name'])) {
        $result['valid'] = false;
        $result['message'] = 'El archivo no es una imagen válida';
        return $result;
    }
    
    return $result;
}