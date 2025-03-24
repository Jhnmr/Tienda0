<?php
class contactcontroller {
    public function index() {
        $pageTitle = "Contacto";
        include __DIR__ . '/../../views/shop/contact.php';
    }
    
    public function send() {
        // Validar y procesar el formulario de contacto
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                $_SESSION['error_message'] = 'Error de seguridad. Por favor, intente de nuevo.';
                redirect('/contacto');
                return;
            }
            
            // Validar campos
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
            $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
            
            $errors = [];
            
            if (empty($name)) {
                $errors[] = 'Por favor, ingrese su nombre.';
            }
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Por favor, ingrese un correo electrónico válido.';
            }
            
            if (empty($message)) {
                $errors[] = 'Por favor, ingrese su mensaje.';
            }
            
            if (!empty($errors)) {
                $_SESSION['error_message'] = implode('<br>', $errors);
                $_SESSION['form_data'] = $_POST;
                redirect('/contacto');
                return;
            }
            
            // Aquí implementarías el envío de email
            // ...
            
            $_SESSION['success_message'] = 'Su mensaje ha sido enviado correctamente. Nos pondremos en contacto con usted pronto.';
            redirect('/contacto');
        }
    }
}