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
$witness_id = $_GET['id'] ?? null;

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
$witness = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'];
        $contact = $_POST['contact'] ?? '';
        $statement = $_POST['statement'] ?? '';
        $date_recorded = $_POST['date_recorded'] ?? date('Y-m-d H:i:s');
        
        if ($witness_id) {
            // Update existing witness
            $stmt = $pdo->prepare("UPDATE witness SET 
                                 Name = ?, 
                                 Contact = ?, 
                                 Statement = ?,
                                 Date_Recorded = ?
                                 WHERE Witness_ID = ?");
            $stmt->execute([$name, $contact, $statement, $date_recorded, $witness_id]);
            
            // Redirect to success page then case_details.php
            $success_message = "Witness updated successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        } else {
            // Create new witness - use current user's ID as Recorded_By
            $recorded_by = $_SESSION['user_id'];
            
            // Insert witness
            $stmt = $pdo->prepare("INSERT INTO witness 
                                 (Name, Contact, Statement, Date_Recorded, Recorded_By, Cases_ID) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $contact, $statement, $date_recorded, $recorded_by, $case_id]);
            
            $witness_id = $pdo->lastInsertId();
            
            // Redirect to success page then case_details.php
            $success_message = "Witness added successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch witness data if editing
if ($witness_id) {
    $stmt = $pdo->prepare("SELECT w.*, u.Full_Name as Recorded_By_Name 
                          FROM witness w
                          JOIN user_id u ON w.Recorded_By = u.User_ID
                          WHERE Witness_ID = ?");
    $stmt->execute([$witness_id]);
    $witness = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$witness || $witness['Cases_ID'] != $case_id) {
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
    <title><?= $witness_id ? 'Edit' : 'Add' ?> Witness</title>
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
        
        .recorded-by {
            background-color: var(--cream);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="bi bi-person-vcard"></i> <?= $witness_id ? 'Edit Witness Details' : 'Add New Witness' ?></h2>
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
                
                <?php if ($witness_id && isset($witness['Recorded_By_Name'])): ?>
                    <div class="recorded-by">
                        <strong>Recorded By:</strong> <?= htmlspecialchars($witness['Recorded_By_Name']) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <h5 class="mb-3" style="color: var(--primary-brown); border-bottom: 1px solid var(--lighter-brown); padding-bottom: 8px;">
                            <i class="bi bi-person-lines-fill"></i> Personal Information
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label required">Full Name</label>
                                <input type="text" id="name" name="name" class="form-control form-control-lg" 
                                       value="<?= htmlspecialchars($witness['Name'] ?? '') ?>" required>
                                <div class="invalid-feedback">Please enter the witness's full name.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="contact" class="form-label">Contact Information</label>
                                <input type="text" id="contact" name="contact" class="form-control" 
                                       value="<?= htmlspecialchars($witness['Contact'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="mb-3" style="color: var(--primary-brown); border-bottom: 1px solid var(--lighter-brown); padding-bottom: 8px;">
                            <i class="bi bi-journal-text"></i> Case Information
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="date_recorded" class="form-label">Date Recorded</label>
                                <input type="datetime-local" id="date_recorded" name="date_recorded" class="form-control" 
                                       value="<?= isset($witness['Date_Recorded']) ? date('Y-m-d\TH:i', strtotime($witness['Date_Recorded'])) : date('Y-m-d\TH:i') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="mb-3" style="color: var(--primary-brown); border-bottom: 1px solid var(--lighter-brown); padding-bottom: 8px;">
                            <i class="bi bi-chat-square-text-fill"></i> Witness Statement
                        </h5>
                        
                        <div class="mb-3">
                            <label for="statement" class="form-label">Detailed Statement</label>
                            <textarea id="statement" name="statement" class="form-control" rows="5"><?= htmlspecialchars($witness['Statement'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="case_details.php?id=<?= $case_id ?>" class="btn btn-secondary btn-lg px-4">
                            <i class="bi bi-arrow-left"></i> Back to Case
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-save"></i> <?= $witness_id ? 'Update' : 'Save' ?> Witness
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