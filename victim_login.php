<?php
session_start();
require_once 'db_config_victim.php';

// Redirect if already logged in
if(isset($_SESSION['user_id']) && isset($_SESSION['user_type_id']) && $_SESSION['user_type_id'] == 3) {
    header("Location: victim_portal.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if(empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $stmt = $db->prepare("
                SELECT u.User_ID, u.Username, u.Password, u.Full_Name, v.Victim_ID 
                FROM user_id u
                LEFT JOIN victim v ON u.User_ID = v.User_ID
                WHERE u.Username = ? AND u.user_type_id = 3
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Modified password verification for plain text
            if($user && $password === $user['Password']) {
                $_SESSION['user_id'] = $user['User_ID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['full_name'] = $user['Full_Name'];
                $_SESSION['victim_id'] = $user['Victim_ID'];
                $_SESSION['user_type'] = 'victim';
                
                header("Location: victim_portal.php");
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Victim Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 350px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color:rgb(144, 110, 126);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .forgot-password {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 style="text-align: center;">Victim Login</h2>
        
        <?php if(!empty($error)): ?>
            <div class="error" style="color: red; padding: 10px; background-color: #ffeeee; border-radius: 4px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
    </div>
</body>
</html>