<?php
class issuecontroller {
    public function index() {
        $pageTitle = "Reportar un problema";
        include __DIR__ . '/../../views/shop/issue.php';
    }
    
    public function report() {
        // Validar y procesar el formulario de reporte
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
                redirect('/reportar-problema');
                return;
            }
            
            // Validar campos
            $issue_type = filter_input(INPUT_POST, 'issue_type', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
            
            $errors = [];
            
            if (empty($issue_type)) {
                $errors[] = 'Por favor, seleccione un tipo de problema.';
            }
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Por favor, ingrese un correo electrónico válido.';
            }
            
            if (empty($description)) {
                $errors[] = 'Por favor, describa el problema.';
            }
            
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
                $_SESSION['form_data'] = $_POST;
                redirect('/reportar-problema');
                return;
            }
            
            // Aquí implementarías el guardado del reporte
            // ...
            
            $_SESSION['success_message'] = 'Su reporte ha sido enviado correctamente. Gracias por ayudarnos a mejorar.';
            redirect('/reportar-problema');
        }
    }
}