<?php
require_once 'db_config.php';
require_once 'auth.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get parameters
$case_id = $_GET['case_id'] ?? null;
$victim_id = $_GET['id'] ?? null;

// Validate case_id
if (!$case_id) {
    header("Location: lawyer_dashboard.php");
    exit();
}

// Verify lawyer assignment
$stmt = $pdo->prepare("SELECT COUNT(*) FROM lawyers_id WHERE User_ID = ? AND Cases_ID = ?");
$stmt->execute([$_SESSION['user_id'], $case_id]);
if ($stmt->fetchColumn() == 0) {
    header("Location: unauthorized.php");
    exit();
}

$error = '';
$victim = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'];
        $contact = $_POST['contact'] ?? '';
        $address = $_POST['address'] ?? '';
        $statement = $_POST['statement'] ?? '';
        $date_recorded = $_POST['date_recorded'] ?? date('Y-m-d H:i:s');
        
        if ($victim_id) {
            // Update existing victim
            $stmt = $pdo->prepare("UPDATE victim SET 
                                 Name = ?, 
                                 Contact = ?, 
                                 Address = ?, 
                                 Statement = ?,
                                 Date_Recorded = ?
                                 WHERE Victim_ID = ?");
            $stmt->execute([$name, $contact, $address, $statement, $date_recorded, $victim_id]);
            
            // Redirect to success page then case details
            $success_message = "Victim details updated successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        } else {
            // Create new victim
            $recorded_by = $_SESSION['user_id'];
            $stmt = $pdo->prepare("INSERT INTO victim 
                                 (Name, Contact, Address, Statement, Date_Recorded, Recorded_By_User_ID, Cases_ID) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $contact, $address, $statement, $date_recorded, $recorded_by, $case_id]);
            
            $victim_id = $pdo->lastInsertId();
            $success_message = "Victim added successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch victim data if editing
if ($victim_id) {
    $stmt = $pdo->prepare("SELECT * FROM victim WHERE Victim_ID = ?");
    $stmt->execute([$victim_id]);
    $victim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$victim || $victim['Cases_ID'] != $case_id) {
        header("Location: case_details.php?id=$case_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $victim_id ? 'Edit Victim' : 'Add New Victim' ?></title>
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
            max-width: 800px;
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
        
        .btn-secondary {
            background-color: var(--light-brown);
            border-color: var(--light-brown);
        }
        
        .btn-secondary:hover {
            background-color: var(--primary-brown);
            border-color: var(--primary-brown);
        }
        
        .form-control:focus {
            border-color: var(--lighter-brown);
            box-shadow: 0 0 0 0.25rem rgba(141, 110, 99, 0.25);
        }
        
        .required:after {
            content: " *";
            color: #dc3545;
        }
        
        .badge-case {
            background-color: var(--light-brown);
            color: white;
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="bi bi-person-heart"></i> <?= $victim_id ? 'Edit Victim' : 'Add New Victim' ?></h2>
                    <span class="badge badge-case fs-6">Case ID: <?= htmlspecialchars($case_id) ?></span>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <label for="name" class="form-label required">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control form-control-lg" 
                               value="<?= htmlspecialchars($victim['Name'] ?? '') ?>" required>
                        <div class="invalid-feedback">Please enter the victim's full name.</div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="contact" class="form-label">Contact Information</label>
                            <input type="text" id="contact" name="contact" class="form-control" 
                                   value="<?= htmlspecialchars($victim['Contact'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="date_recorded" class="form-label">Date Recorded</label>
                            <input type="datetime-local" id="date_recorded" name="date_recorded" class="form-control" 
                                   value="<?= isset($victim['Date_Recorded']) ? date('Y-m-d\TH:i', strtotime($victim['Date_Recorded'])) : date('Y-m-d\TH:i') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?= htmlspecialchars($victim['Address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="statement" class="form-label">Statement</label>
                        <textarea id="statement" name="statement" class="form-control" rows="5"><?= htmlspecialchars($victim['Statement'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="case_details.php?id=<?= $case_id ?>" class="btn btn-secondary btn-lg">
                            <i class="bi bi-arrow-left"></i> Back to Case
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> <?= $victim_id ? 'Update' : 'Save' ?> Victim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>