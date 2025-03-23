<?php
// controllers/ProfileController.php
// Controlador para gestionar la vista y actualización del perfil del usuario

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/UserProfile.php';
require_once __DIR__ . '/../utils/csrf.php';

class ProfileController {
    private $userProfile;

    public function __construct($pdo) {
        $this->userProfile = new UserProfile($pdo);
    }

    // Mostrar la vista del perfil
    public function showProfile() {
        // Se asume que el ID del usuario está en la sesión tras el login
        $userId = $_SESSION['user_id'];
        $profile = $this->userProfile->getProfile($userId);
        include __DIR__ . '/../views/auth/profile.php';
    }

    // Actualizar el perfil del usuario
    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!validateCsrfToken($_POST['csrf_token'])) {
                $_SESSION['error_message'] = "Token CSRF inválido";
                header("Location: /profile.php");
                exit;
            }
            $userId = $_SESSION['user_id'];
            $data = [
                'nombres'           => $_POST['nombres'],
                'apellidos'         => $_POST['apellidos'],
                'telefono'          => $_POST['telefono'],
                'fecha_nacimiento'  => $_POST['fecha_nacimiento'],
                'genero'            => $_POST['genero'],
                'marketing_consent' => isset($_POST['marketing_consent']) ? 1 : 0
            ];
            if ($this->userProfile->updateProfile($userId, $data)) {
                $_SESSION['success_message'] = "Perfil actualizado correctamente";
            } else {
                $_SESSION['error_message'] = "Error al actualizar el perfil";
            }
            header("Location: /profile.php");
            exit;
        }
    }
}

// Ruteo básico según el método HTTP
$profileController = new ProfileController($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profileController->updateProfile();
} else {
    $profileController->showProfile();
}
?>
