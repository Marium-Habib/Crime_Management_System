<?php
session_start();
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // Prepare SQL to check user credentials using PDO
        $stmt = $pdo->prepare("SELECT u.User_ID, u.Username, u.Password, u.user_type_id, o.Officer_ID 
                             FROM user_id u 
                             LEFT JOIN officer_id o ON u.User_ID = o.User_ID 
                             WHERE u.Username = :username AND u.user_type_id = 1");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password (plaintext comparison - not recommended for production)
            if ($password === $user['Password']) {
                // Authentication successful
                $_SESSION['user_id'] = $user['User_ID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['officer_id'] = $user['Officer_ID'];
                $_SESSION['user_type_id'] = $user['user_type_id'];
                
                header("Location: police_dashboard.php");
                exit();
            }
        }
        
        // Authentication failed
        header("Location: police_login.php?error=1");
        exit();
        
    } catch (PDOException $e) {
        // Log error and redirect
        error_log("Login error: " . $e->getMessage());
        header("Location: police_login.php?error=2");
        exit();
    }
}

// No need to close PDO connection explicitly
?>