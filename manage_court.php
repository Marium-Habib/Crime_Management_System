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

// Get case ID and hearing ID
$case_id = $_GET['case_id'] ?? null;
$hearing_id = $_GET['hearing_id'] ?? null;

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
$success = '';
$court = null;
$hearings = [];
$hearing = null;

// Fetch court information for this case
$stmt = $pdo->prepare("SELECT * FROM court WHERE Cases_ID = ?");
$stmt->execute([$case_id]);
$court = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all hearings for this case
$stmt = $pdo->prepare("SELECT h.*, c.Court_Name 
                      FROM hearing_dates h
                      JOIN court c ON h.Court_ID = c.Court_ID
                      WHERE h.Cases_ID = ?
                      ORDER BY h.Hearing_Date ASC");
$stmt->execute([$case_id]);
$hearings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If we're editing a hearing, fetch its data
if ($hearing_id) {
    $stmt = $pdo->prepare("SELECT * FROM hearing_dates WHERE Hearing_ID = ? AND Cases_ID = ?");
    $stmt->execute([$hearing_id, $case_id]);
    $hearing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hearing) {
        header("Location: manage_court.php?case_id=$case_id");
        exit();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['hearing_action'])) {
            // Handle hearing form submission
            $hearing_date = htmlspecialchars(trim($_POST['hearing_date']));
            $purpose = htmlspecialchars(trim($_POST['purpose']));
            $court_id = $court['Court_ID'];

            if ($hearing_id) {
                // Update existing hearing
                $stmt = $pdo->prepare("UPDATE hearing_dates SET 
                                     Hearing_Date = ?, 
                                     Purpose = ?,
                                     Court_ID = ?
                                     WHERE Hearing_ID = ?");
                $stmt->execute([$hearing_date, $purpose, $court_id, $hearing_id]);
                
                $success_message = "Hearing date updated successfully!";
            } else {
                // Create new hearing
                $stmt = $pdo->prepare("INSERT INTO hearing_dates 
                                     (Hearing_Date, Purpose, Court_ID, Cases_ID) 
                                     VALUES (?, ?, ?, ?)");
                $stmt->execute([$hearing_date, $purpose, $court_id, $case_id]);
                
                $success_message = "Hearing date added successfully!";
            }
            
            $redirect_url = "manage_court.php?case_id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $court ? 'Court & Hearing Management' : 'Add Court Information' ?></title>
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
            max-width: 1000px;
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
        
        .btn-info {
            background-color: var(--lighter-brown);
            border-color: var(--lighter-brown);
            color: var(--dark-brown);
        }
        
        .btn-info:hover {
            background-color: var(--light-brown);
            border-color: var(--light-brown);
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
        
        .court-info {
            background-color: var(--cream);
            padding: 20px;
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
        
        .hearing-item {
            border-left: 3px solid var(--light-brown);
            padding: 15px;
            margin-bottom: 15px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .hearing-date {
            font-weight: 600;
            color: var(--primary-brown);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table th {
            background-color: var(--lighter-brown);
            color: var(--dark-brown);
        }
        
        .action-buttons {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="bi bi-building"></i> Court & Hearing Management</h2>
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
                
                <!-- Court Information Section -->
               <!-- Court Information Section -->
<div class="mb-5">
    <h5 class="section-header">
        <i class="bi bi-building"></i> Court Information
    </h5>
    
    <?php if ($court): ?>
        <div class="court-info">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Court Name:</strong> <?= htmlspecialchars($court['Court_Name']) ?></p>
                    <p><strong>Judge Name:</strong> <?= htmlspecialchars($court['Judge_Name']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Address:</strong> <?= htmlspecialchars($court['Address']) ?></p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> No court information has been added for this case.
            <div class="mt-2">
                <a href="add_court.php?case_id=<?= $case_id ?>" class="btn btn-primary">
                    <i class="bi bi-plus"></i> Add Court Information
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
                
                <!-- Hearing Dates Section -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="section-header mb-0">
                            <i class="bi bi-calendar-event"></i> Hearing Dates
                        </h5>
                        <?php if ($court): ?>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#hearingModal">
                                <i class="bi bi-plus"></i> Add Hearing Date
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($court): ?>
                        <?php if (count($hearings) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Purpose</th>
                                            <th>Court</th>
                                            <th class="action-buttons">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($hearings as $h): ?>
                                            <tr>
                                                <td><?= date('M d, Y h:i A', strtotime($h['Hearing_Date'])) ?></td>
                                                <td><?= htmlspecialchars($h['Purpose']) ?></td>
                                                <td><?= htmlspecialchars($h['Court_Name']) ?></td>
                                                <td class="action-buttons">
                                                    <a href="manage_court.php?case_id=<?= $case_id ?>&hearing_id=<?= $h['Hearing_ID'] ?>" 
                                                       class="btn btn-sm btn-info me-1">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                    <a href="delete_hearing.php?case_id=<?= $case_id ?>&hearing_id=<?= $h['Hearing_ID'] ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this hearing date?');">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No hearing dates have been scheduled yet.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Please add court information first before managing hearing dates.
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="case_details.php?id=<?= $case_id ?>" class="btn btn-secondary btn-lg px-4">
                        <i class="bi bi-arrow-left"></i> Back to Case
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Hearing Modal -->
    <div class="modal fade" id="hearingModal" tabindex="-1" aria-labelledby="hearingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--primary-brown); color: white;">
                    <h5 class="modal-title" id="hearingModalLabel">
                        <i class="bi bi-calendar-plus"></i> <?= $hearing_id ? 'Edit' : 'Add' ?> Hearing Date
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="hearing_action" value="1">
                        
                        <div class="mb-3">
                            <label for="hearing_date" class="form-label required">Hearing Date & Time</label>
                            <input type="datetime-local" id="hearing_date" name="hearing_date" class="form-control" 
                                   value="<?= isset($hearing['Hearing_Date']) ? date('Y-m-d\TH:i', strtotime($hearing['Hearing_Date'])) : '' ?>"
                                   required>
                            <div class="invalid-feedback">Please select a hearing date and time</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="purpose" class="form-label required">Purpose</label>
                            <input type="text" id="purpose" name="purpose" class="form-control" 
                                   value="<?= isset($hearing['Purpose']) ? htmlspecialchars($hearing['Purpose']) : '' ?>"
                                   placeholder="Purpose of the hearing" required>
                            <div class="invalid-feedback">Please enter the purpose of the hearing</div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                <i class="bi bi-x"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> <?= $hearing_id ? 'Update' : 'Save' ?> Hearing
                            </button>
                        </div>
                    </form>
                </div>
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
        
        // Open modal if we're editing a hearing
        <?php if ($hearing_id && $hearing): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var hearingModal = new bootstrap.Modal(document.getElementById('hearingModal'));
                hearingModal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>