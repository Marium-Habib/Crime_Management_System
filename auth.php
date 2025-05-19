<?php
// Start session at the very beginning
session_start();

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verify session data against database
require_once 'db_config.php';

try {
    $stmt = $pdo->prepare("SELECT user_type_id FROM user_id WHERE User_ID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['user_type_id'] != 2) { // 2 is for lawyer
        header("Location: unauthorized.php");
        exit();
    }
    
    // Optional: Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>