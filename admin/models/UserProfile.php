<?php
// models/UserProfile.php
// Modelo para gestionar el perfil extendido del usuario (tabla: usuario_perfil)

class UserProfile {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Obtener perfil del usuario por su ID
    public function getProfile($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM usuario_perfil WHERE id_usuario = :id");
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar el perfil del usuario
    public function updateProfile($userId, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE usuario_perfil 
            SET 
                nombres = :nombres, 
                apellidos = :apellidos, 
                telefono = :telefono, 
                fecha_nacimiento = :fecha_nacimiento, 
                genero = :genero, 
                marketing_consent = :marketing_consent, 
                fecha_actualizacion = NOW() 
            WHERE id_usuario = :id
        ");
        return $stmt->execute([
            'nombres'           => $data['nombres'],
            'apellidos'         => $data['apellidos'],
            'telefono'          => $data['telefono'],
            'fecha_nacimiento'  => $data['fecha_nacimiento'],
            'genero'            => $data['genero'],
            'marketing_consent' => $data['marketing_consent'],
            'id'                => $userId
        ]);
    }
}
?>
