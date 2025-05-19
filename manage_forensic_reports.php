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

// Get case and report IDs
$case_id = $_GET['case_id'] ?? null;
$report_id = $_GET['id'] ?? null;

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
$report = null;
$file_error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize inputs
        $lab_name = htmlspecialchars(trim($_POST['lab_name']));
        $report_date = $_POST['report_date'] ?? date('Y-m-d H:i:s');
        $findings = htmlspecialchars(trim($_POST['findings']));
        
        // Handle file upload
        $file_path = null;
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/forensic_reports/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = basename($_FILES['report_file']['name']);
            $target_path = $upload_dir . uniqid() . '_' . $file_name;
            
            // Validate file type
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $file_type = $_FILES['report_file']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                if (move_uploaded_file($_FILES['report_file']['tmp_name'], $target_path)) {
                    $file_path = $target_path;
                } else {
                    $file_error = "Failed to upload file.";
                }
            } else {
                $file_error = "Invalid file type. Only PDF, JPEG, PNG, and Word documents are allowed.";
            }
        } elseif (isset($_FILES['report_file']) && $_FILES['report_file']['error'] != UPLOAD_ERR_NO_FILE) {
            $file_error = "File upload error: " . $_FILES['report_file']['error'];
        }

        if ($report_id) {
            // Update existing report
            if ($file_path) {
                // Delete old file if exists
                if (!empty($report['File_Path']) && file_exists($report['File_Path'])) {
                    unlink($report['File_Path']);
                }
                
                $stmt = $pdo->prepare("UPDATE forensic_report SET 
                                     Lab_Name = ?, 
                                     Report_Date = ?,
                                     Findings = ?,
                                     File_Path = ?
                                     WHERE Report_ID = ? AND Cases_ID = ?");
                $stmt->execute([$lab_name, $report_date, $findings, $file_path, $report_id, $case_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE forensic_report SET 
                                     Lab_Name = ?, 
                                     Report_Date = ?,
                                     Findings = ?
                                     WHERE Report_ID = ? AND Cases_ID = ?");
                $stmt->execute([$lab_name, $report_date, $findings, $report_id, $case_id]);
            }
            
            $success_message = "Forensic report updated successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        } else {
            // Create new report
            $stmt = $pdo->prepare("INSERT INTO forensic_report 
                                 (Lab_Name, Report_Date, Findings, File_Path, Cases_ID) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$lab_name, $report_date, $findings, $file_path, $case_id]);
            
            $report_id = $pdo->lastInsertId();
            $success_message = "Forensic report added successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch report data if editing
if ($report_id) {
    $stmt = $pdo->prepare("SELECT * FROM forensic_report WHERE Report_ID = ? AND Cases_ID = ?");
    $stmt->execute([$report_id, $case_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
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
    <title><?= $report_id ? 'Edit' : 'Add' ?> Forensic Report</title>
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
        
        .section-header {
            color: var(--primary-brown); 
            border-bottom: 1px solid var(--lighter-brown); 
            padding-bottom: 8px;
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .file-preview {
            margin-top: 10px;
        }
        
        .file-preview a {
            color: var(--primary-brown);
            text-decoration: none;
        }
        
        .file-preview a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="bi bi-file-earmark-medical-fill"></i> <?= $report_id ? 'Edit Forensic Report' : 'Add New Forensic Report' ?></h2>
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
                
                <form method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-card-checklist"></i> Report Details
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="lab_name" class="form-label required">Lab Name</label>
                                <input type="text" id="lab_name" name="lab_name" class="form-control" 
                                       value="<?= htmlspecialchars($report['Lab_Name'] ?? '') ?>" required>
                                <div class="invalid-feedback">Please enter lab name</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="report_date" class="form-label required">Report Date</label>
                                <input type="datetime-local" id="report_date" name="report_date" class="form-control" 
                                       value="<?= isset($report['Report_Date']) ? date('Y-m-d\TH:i', strtotime($report['Report_Date'])) : date('Y-m-d\TH:i') ?>" required>
                                <div class="invalid-feedback">Please select report date</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-card-text"></i> Findings
                        </h5>
                        
                        <div class="mb-3">
                            <label for="findings" class="form-label required">Detailed Findings</label>
                            <textarea id="findings" name="findings" class="form-control" rows="5" required
                                      placeholder="Detailed forensic findings and analysis"><?= htmlspecialchars($report['Findings'] ?? '') ?></textarea>
                            <div class="invalid-feedback">Please enter findings</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-file-earmark-arrow-up"></i> Report File
                        </h5>
                        
                        <div class="mb-3">
                            <label for="report_file" class="form-label">Upload Report File</label>
                            <input type="file" id="report_file" name="report_file" class="form-control">
                            <small class="text-muted">PDF, Word, JPEG, or PNG files only (Max 5MB)</small>
                            
                            <?php if ($file_error): ?>
                                <div class="alert alert-danger mt-2">
                                    <i class="bi bi-exclamation-triangle-fill"></i> <?= $file_error ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($report_id && !empty($report['File_Path'])): ?>
                                <div class="file-preview mt-2">
                                    <strong>Current File:</strong> 
                                    <a href="<?= htmlspecialchars($report['File_Path']) ?>" target="_blank">
                                        <i class="bi bi-file-earmark"></i> View Report
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="case_details.php?id=<?= $case_id ?>" class="btn btn-secondary btn-lg px-4">
                            <i class="bi bi-arrow-left"></i> Back to Case
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-save"></i> <?= $report_id ? 'Update' : 'Save' ?> Report
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