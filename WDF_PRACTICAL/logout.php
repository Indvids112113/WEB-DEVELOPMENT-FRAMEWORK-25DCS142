<?php
session_start();
require_once 'db.php';

// 1. Clear Remember Me token from database if a student is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
    try {
        $stmt = $pdo->prepare("UPDATE students SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (\PDOException $e) {
        // Handle or ignore database error on logout
    }
}

// 2. Unset all session variables
$_SESSION = array();

// 3. Destroy the session cookie securely
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Clear the browser Remember-Me cookie
setcookie("remember_me", "", time() - 3600, "/");

// 5. Destroy the server session
session_destroy();

// 6. Redirect back to the login page
header("Location: login.php");
exit;
?>