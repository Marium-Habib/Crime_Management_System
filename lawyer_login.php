<?php
session_start();
require_once 'db_config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        // First check user credentials
        $stmt = $pdo->prepare("SELECT User_ID, Username, Password, user_type_id 
                              FROM user_id 
                              WHERE Username = ? AND user_type_id = 2");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($password === $user['Password']) {
                // Now get lawyer information
                $lawyer_stmt = $pdo->prepare("SELECT Lawyer_ID FROM lawyers_id WHERE User_ID = ?");
                $lawyer_stmt->execute([$user['User_ID']]);
                $lawyer = $lawyer_stmt->fetch(PDO::FETCH_ASSOC);
                
                $_SESSION['user_id'] = $user['User_ID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['lawyer_id'] = $lawyer['Lawyer_ID'] ?? null;
                $_SESSION['user_type'] = 'lawyer';
                
                header("Location: lawyer_dashboard.php");
                exit();
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "Username not found or not a lawyer account";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!-- Rest of your HTML remains the same -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lawyer Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('lawyer_login.jpg'); /* Image ka path */
            background-size: cover;  /* Image ko screen ke size ke mutabiq fit karne ke liye */
            background-repeat: no-repeat; /* Image repeat na ho */
            background-position: center; /* Image ko center me rakhne ke liye */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            position: relative;
        }

        .login-container {
            background-color:rgb(163, 148, 134); /* Wheat color */
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgb(57, 29, 0);
            width: 350px;
        }
        h2 {
            text-align: center;
            color:rgb(59, 24, 0);
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color:rgb(49, 22, 1);
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solidrgb(67, 49, 107);
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color:rgb(55, 28, 2); /* Sienna */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color:rgb(59, 30, 10); /* SaddleBrown */
        }
        .error {
            color:rgb(69, 38, 15);
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Lawyer Portal Login</h2>
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
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