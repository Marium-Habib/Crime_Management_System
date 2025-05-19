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

// Get case and evidence IDs
$case_id = $_GET['case_id'] ?? null;
$evidence_id = $_GET['id'] ?? null;

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
$evidence = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize inputs
        $type = htmlspecialchars(trim($_POST['type']));
        $status = htmlspecialchars(trim($_POST['status']));
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $location_found = htmlspecialchars(trim($_POST['location_found'] ?? ''));
        $date_found = $_POST['date_found'] ?? date('Y-m-d H:i:s');
        $collected_by = $_SESSION['user_id'];

        if ($evidence_id) {
            // Update existing evidence
            $stmt = $pdo->prepare("UPDATE evidence_id SET 
                                 Type = ?, 
                                 Status = ?,
                                 Description = ?,
                                 Location_Found = ?,
                                 Date_Found = ?,
                                 Collected_By = ?
                                 WHERE Evidence_ID = ?");
            $stmt->execute([$type, $status, $description, $location_found, $date_found, $collected_by, $evidence_id]);
            
            $success_message = "Evidence updated successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        } else {
            // Create new evidence
            $stmt = $pdo->prepare("INSERT INTO evidence_id 
                                 (Type, Status, Description, Location_Found, Date_Found, 
                                  Collected_By, Cases_ID) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$type, $status, $description, $location_found, $date_found, 
                          $collected_by, $case_id]);
            
            $evidence_id = $pdo->lastInsertId();
            $success_message = "Evidence added successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch evidence data if editing
if ($evidence_id) {
    $stmt = $pdo->prepare("SELECT e.*, u.Full_Name as Collected_By_Name 
                          FROM evidence_id e
                          JOIN user_id u ON e.Collected_By = u.User_ID
                          WHERE Evidence_ID = ?");
    $stmt->execute([$evidence_id]);
    $evidence = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$evidence || $evidence['Cases_ID'] != $case_id) {
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
    <title><?= $evidence_id ? 'Edit' : 'Add' ?> Evidence</title>
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
        
        .collected-by {
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
                    <h2 class="mb-0"><i class="bi bi-archive-fill"></i> <?= $evidence_id ? 'Edit Evidence' : 'Add New Evidence' ?></h2>
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
                
                <?php if ($evidence_id && isset($evidence['Collected_By_Name'])): ?>
                    <div class="collected-by">
                        <strong>Collected By:</strong> <?= htmlspecialchars($evidence['Collected_By_Name']) ?>
                        <br>
                        <small class="text-muted">on <?= isset($evidence['Date_Found']) ? date('M d, Y h:i A', strtotime($evidence['Date_Found'])) : 'N/A' ?></small>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-card-checklist"></i> Evidence Details
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label required">Evidence Type</label>
                                <select id="type" name="type" class="form-select form-select-lg" required>
                                    <option value="">Select type</option>
                                    <option value="Weapon" <?= isset($evidence['Type']) && $evidence['Type'] === 'Weapon' ? 'selected' : '' ?>>Weapon</option>
                                    <option value="Document" <?= isset($evidence['Type']) && $evidence['Type'] === 'Document' ? 'selected' : '' ?>>Document</option>
                                    <option value="Clothing" <?= isset($evidence['Type']) && $evidence['Type'] === 'Clothing' ? 'selected' : '' ?>>Clothing</option>
                                    <option value="Electronics" <?= isset($evidence['Type']) && $evidence['Type'] === 'Electronics' ? 'selected' : '' ?>>Electronics</option>
                                    <option value="Biological" <?= isset($evidence['Type']) && $evidence['Type'] === 'Biological' ? 'selected' : '' ?>>Biological</option>
                                    <option value="Other" <?= isset($evidence['Type']) && $evidence['Type'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                                <div class="invalid-feedback">Please select evidence type</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="status" class="form-label required">Status</label>
                                <select id="status" name="status" class="form-select form-select-lg" required>
                                    <option value="">Select status</option>
                                    <option value="Collected" <?= isset($evidence['Status']) && $evidence['Status'] === 'Collected' ? 'selected' : '' ?>>Collected</option>
                                    <option value="Analyzed" <?= isset($evidence['Status']) && $evidence['Status'] === 'Analyzed' ? 'selected' : '' ?>>Analyzed</option>
                                    <option value="Stored" <?= isset($evidence['Status']) && $evidence['Status'] === 'Stored' ? 'selected' : '' ?>>Stored</option>
                                    <option value="Disposed" <?= isset($evidence['Status']) && $evidence['Status'] === 'Disposed' ? 'selected' : '' ?>>Disposed</option>
                                </select>
                                <div class="invalid-feedback">Please select evidence status</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-geo-alt-fill"></i> Discovery Information
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="location_found" class="form-label">Location Found</label>
                                <input type="text" id="location_found" name="location_found" class="form-control" 
                                       value="<?= htmlspecialchars($evidence['Location_Found'] ?? '') ?>"
                                       placeholder="Where the evidence was found">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="date_found" class="form-label">Date Found</label>
                                <input type="datetime-local" id="date_found" name="date_found" class="form-control" 
                                       value="<?= isset($evidence['Date_Found']) ? date('Y-m-d\TH:i', strtotime($evidence['Date_Found'])) : date('Y-m-d\TH:i') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-card-text"></i> Description
                        </h5>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Detailed Description</label>
                            <textarea id="description" name="description" class="form-control" rows="5"
                                      placeholder="Detailed description of the evidence including any identifying marks or features"><?= htmlspecialchars($evidence['Description'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="case_details.php?id=<?= $case_id ?>" class="btn btn-secondary btn-lg px-4">
                            <i class="bi bi-arrow-left"></i> Back to Case
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-save"></i> <?= $evidence_id ? 'Update' : 'Save' ?> Evidence
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