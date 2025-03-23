<?php
// utils/password_utils.php
// Funciones para el manejo seguro de contraseñas

// Hash de contraseña usando bcrypt
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Verificar una contraseña contra su hash
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>
