<?php
ob_start();
require_once 'db_config.php';
require_once 'auth.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get case and suspect IDs
$case_id = $_GET['case_id'] ?? null;
$suspect_id = $_GET['id'] ?? null;

// Validate case ID
if (!$case_id || !is_numeric($case_id)) {
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
$suspect = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize inputs
        $name = htmlspecialchars(trim($_POST['name']));
        $contact = htmlspecialchars(trim($_POST['contact'] ?? ''));
        $address = htmlspecialchars(trim($_POST['address'] ?? ''));
        $statement = htmlspecialchars(trim($_POST['statement'] ?? ''));
        $criminal_history = htmlspecialchars(trim($_POST['criminal_history'] ?? ''));
        $date_recorded = $_POST['date_recorded'] ?? date('Y-m-d H:i:s');

        if ($suspect_id) {
            // Update existing suspect
            $stmt = $pdo->prepare("UPDATE xsuspect_id SET 
                                 Name = ?, 
                                 Contact = ?,
                                 Address = ?,
                                 Statement = ?,
                                 Criminal_History = ?,
                                 Date_Recorded = ?
                                 WHERE Suspect_ID = ?");
            $stmt->execute([$name, $contact, $address, $statement, $criminal_history, $date_recorded, $suspect_id]);
            
            $success_message = "Suspect updated successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        } else {
            // Create new suspect
            $recorded_by = $_SESSION['user_id'];
            $stmt = $pdo->prepare("INSERT INTO xsuspect_id 
                                 (Name, Contact, Address, Statement, Criminal_History, 
                                  Date_Recorded, Recorded_By_User_ID, Cases_ID) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $contact, $address, $statement, $criminal_history, 
                          $date_recorded, $recorded_by, $case_id]);
            
            $suspect_id = $pdo->lastInsertId();
            $success_message = "Suspect added successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch suspect data if editing
if ($suspect_id) {
    $stmt = $pdo->prepare("SELECT s.*, u.Full_Name as Recorded_By_Name 
                          FROM xsuspect_id s
                          JOIN user_id u ON s.Recorded_By_User_ID = u.User_ID
                          WHERE Suspect_ID = ?");
    $stmt->execute([$suspect_id]);
    $suspect = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$suspect || $suspect['Cases_ID'] != $case_id) {
        header("Location: case_details.php?id=$case_id");
        exit();
    }
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $suspect_id ? 'Edit' : 'Add' ?> Suspect</title>
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
            margin: 2rem auto;
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
            border-left: 4px solid var(--primary-brown);
        }
        
        .section-header {
            color: var(--primary-brown); 
            border-bottom: 1px solid var(--lighter-brown); 
            padding-bottom: 8px;
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="bi bi-person-fill-exclamation"></i> <?= $suspect_id ? 'Edit Suspect' : 'Add New Suspect' ?></h2>
                    <span class="badge badge-case fs-6">Case ID: <?= htmlspecialchars($case_id) ?></span>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($suspect_id && isset($suspect['Recorded_By_Name'])): ?>
                    <div class="recorded-by">
                        <strong>Recorded By:</strong> <?= htmlspecialchars($suspect['Recorded_By_Name']) ?>
                        <br>
                        <small class="text-muted">on <?= isset($suspect['Date_Recorded']) ? date('M d, Y h:i A', strtotime($suspect['Date_Recorded'])) : 'N/A' ?></small>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-person-lines-fill"></i> Personal Information
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label required">Full Name</label>
                                <input type="text" id="name" name="name" class="form-control form-control-lg" 
                                       value="<?= htmlspecialchars($suspect['Name'] ?? '') ?>" required>
                                <div class="invalid-feedback">Please enter suspect's full name</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="contact" class="form-label">Contact Information</label>
                                <input type="text" id="contact" name="contact" class="form-control" 
                                       value="<?= htmlspecialchars($suspect['Contact'] ?? '') ?>"
                                       placeholder="Phone or email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-house-door"></i> Address Information
                        </h5>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Complete Address</label>
                            <textarea id="address" name="address" class="form-control" rows="3"
                                      placeholder="Current residential address"><?= htmlspecialchars($suspect['Address'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-journal-text"></i> Case Information
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="date_recorded" class="form-label">Date Recorded</label>
                                <input type="datetime-local" id="date_recorded" name="date_recorded" class="form-control" 
                                       value="<?= isset($suspect['Date_Recorded']) ? date('Y-m-d\TH:i', strtotime($suspect['Date_Recorded'])) : date('Y-m-d\TH:i') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-chat-square-text"></i> Statement
                        </h5>
                        
                        <div class="mb-3">
                            <label for="statement" class="form-label">Suspect's Statement</label>
                            <textarea id="statement" name="statement" class="form-control" rows="5"
                                      placeholder="Detailed statement from the suspect"><?= htmlspecialchars($suspect['Statement'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-shield-exclamation"></i> Criminal History
                        </h5>
                        
                        <div class="mb-3">
                            <label for="criminal_history" class="form-label">Prior Criminal Record</label>
                            <textarea id="criminal_history" name="criminal_history" class="form-control" rows="5"
                                      placeholder="Any known prior criminal activities or convictions"><?= htmlspecialchars($suspect['Criminal_History'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="case_details.php?id=<?= $case_id ?>" class="btn btn-secondary btn-lg px-4">
                            <i class="bi bi-arrow-left"></i> Back to Case
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-save"></i> <?= $suspect_id ? 'Update' : 'Save' ?> Suspect
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