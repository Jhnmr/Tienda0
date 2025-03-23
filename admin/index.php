<?php
// Start session if not already started
session_start();

// Redirect to admin login or dashboard
if (isset($_SESSION['user_id'])) {
    // If user is logged in, redirect to dashboard
    header("Location: /admin/dashboard.php");
    exit;
} else {
    // If not logged in, redirect to login page
    header("Location: /admin/login.php");
    // test.php (place in project root)
    echo "Hello World! PHP is working!";
    phpinfo(); // Shows PHP configuration
    exit;
}
