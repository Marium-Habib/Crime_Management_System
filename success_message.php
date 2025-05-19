<?php
require_once 'db_config.php';
require_once 'auth.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get parameters
$message = $_GET['message'] ?? 'Operation completed successfully!';
$redirect_url = $_GET['redirect'] ?? 'lawyer_dashboard.php';
$delay = $_GET['delay'] ?? 3; // Default 3 seconds delay
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-brown: #5D4037;
            --light-brown: #8D6E63;
            --lighter-brown: #BCAAA4;
            --cream: #EFEBE9;
            --dark-brown: #3E2723;
        }
        
        body {
            background-color: var(--cream);
            color: var(--dark-brown);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .card-header {
            background-color: var(--primary-brown);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-brown);
            border-color: var(--primary-brown);
        }
        
        .btn-primary:hover {
            background-color: var(--dark-brown);
            border-color: var(--dark-brown);
        }
    </style>
    <meta http-equiv="refresh" content="<?= $delay ?>;url=<?= $redirect_url ?>">
</head>
<body>
    <div class="container py-5">
        <div class="card">
            <div class="card-header text-center">
                <h2 class="mb-0"><i class="bi bi-check-circle-fill"></i> Success</h2>
            </div>
            <div class="card-body text-center p-5">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill" style="font-size: 4rem; color: var(--primary-brown);"></i>
                </div>
                <h4 class="mb-4"><?= htmlspecialchars($message) ?></h4>
                <p>You will be redirected automatically in <?= $delay ?> seconds...</p>
                <a href="<?= $redirect_url ?>" class="btn btn-primary mt-3">
                    <i class="bi bi-arrow-left"></i> Go Now
                </a>
            </div>
        </div>
    </div>
</body>
</html>