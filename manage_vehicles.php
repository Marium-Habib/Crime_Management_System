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

// Get case and vehicle IDs
$case_id = $_GET['case_id'] ?? null;
$vehicle_id = $_GET['id'] ?? null;

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
$vehicle = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize inputs
        $plate_number = htmlspecialchars(trim($_POST['plate_number']));
        $make = htmlspecialchars(trim($_POST['make']));
        $model = htmlspecialchars(trim($_POST['model']));
        $color = htmlspecialchars(trim($_POST['color']));
        $owner_name = htmlspecialchars(trim($_POST['owner_name'] ?? ''));
        
        // Handle suspect ID - use manual input if provided, otherwise dropdown selection
        $suspect_id = null;
        if (!empty($_POST['manual_suspect_id'])) {
            $suspect_id = (int)$_POST['manual_suspect_id'];
        } elseif (!empty($_POST['suspect_id'])) {
            $suspect_id = (int)$_POST['suspect_id'];
        }

        if ($vehicle_id) {
            // Update existing vehicle
            $stmt = $pdo->prepare("UPDATE vehicle SET 
                                 Plate_Number = ?, 
                                 Make = ?,
                                 Model = ?,
                                 Color = ?,
                                 Owner_Name = ?,
                                 Suspect_ID = ?
                                 WHERE Vehicle_ID = ? AND Cases_ID = ?");
            $stmt->execute([$plate_number, $make, $model, $color, $owner_name, $suspect_id, $vehicle_id, $case_id]);
            
            $success_message = "Vehicle updated successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        } else {
            // Create new vehicle
            $stmt = $pdo->prepare("INSERT INTO vehicle 
                                 (Plate_Number, Make, Model, Color, Owner_Name, Cases_ID, Suspect_ID) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$plate_number, $make, $model, $color, $owner_name, $case_id, $suspect_id]);
            
            $vehicle_id = $pdo->lastInsertId();
            $success_message = "Vehicle added successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch vehicle data if editing
if ($vehicle_id) {
    $stmt = $pdo->prepare("SELECT v.*, s.Name as Suspect_Name 
                          FROM vehicle v
                          LEFT JOIN xsuspect_id s ON v.Suspect_ID = s.Suspect_ID
                          WHERE v.Vehicle_ID = ? AND v.Cases_ID = ?");
    $stmt->execute([$vehicle_id, $case_id]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vehicle) {
        header("Location: case_details.php?id=$case_id");
        exit();
    }
}

// Fetch suspects for this case
$suspects = [];
$stmt = $pdo->prepare("SELECT Suspect_ID, Name FROM xsuspect_id WHERE Cases_ID = ?");
$stmt->execute([$case_id]);
$suspects = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $vehicle_id ? 'Edit' : 'Add' ?> Vehicle</title>
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
        
        .suspect-info {
            background-color: var(--cream);
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            border-left: 4px solid var(--primary-brown);
        }
        
        .manual-suspect-toggle {
            cursor: pointer;
            color: var(--primary-brown);
            text-decoration: underline;
            font-size: 0.9rem;
            margin-top: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="bi bi-car-front-fill"></i> <?= $vehicle_id ? 'Edit Vehicle' : 'Add New Vehicle' ?></h2>
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
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-card-checklist"></i> Vehicle Details
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="plate_number" class="form-label required">Plate Number</label>
                                <input type="text" id="plate_number" name="plate_number" class="form-control" 
                                       value="<?= htmlspecialchars($vehicle['Plate_Number'] ?? '') ?>" required>
                                <div class="invalid-feedback">Please enter plate number</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="make" class="form-label required">Make</label>
                                <input type="text" id="make" name="make" class="form-control" 
                                       value="<?= htmlspecialchars($vehicle['Make'] ?? '') ?>" required>
                                <div class="invalid-feedback">Please enter vehicle make</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="model" class="form-label required">Model</label>
                                <input type="text" id="model" name="model" class="form-control" 
                                       value="<?= htmlspecialchars($vehicle['Model'] ?? '') ?>" required>
                                <div class="invalid-feedback">Please enter vehicle model</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="color" class="form-label required">Color</label>
                                <input type="text" id="color" name="color" class="form-control" 
                                       value="<?= htmlspecialchars($vehicle['Color'] ?? '') ?>" required>
                                <div class="invalid-feedback">Please enter vehicle color</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="owner_name" class="form-label">Owner Name</label>
                                <input type="text" id="owner_name" name="owner_name" class="form-control" 
                                       value="<?= htmlspecialchars($vehicle['Owner_Name'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <div id="suspect-select-container">
                                    <label for="suspect_id" class="form-label">Associated Suspect</label>
                                    <select id="suspect_id" name="suspect_id" class="form-select form-select-lg">
                                        <option value="">No suspect assigned</option>
                                        <?php foreach ($suspects as $suspect): ?>
                                            <option value="<?= $suspect['Suspect_ID'] ?>" 
                                                <?= isset($vehicle['Suspect_ID']) && $vehicle['Suspect_ID'] == $suspect['Suspect_ID'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($suspect['Name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="manual-suspect-toggle" onclick="toggleSuspectInput()">Or enter Suspect ID manually</span>
                                </div>
                                
                                <div id="manual-suspect-container" style="display: none;">
                                    <label for="manual_suspect_id" class="form-label">Enter Suspect ID</label>
                                    <input type="number" id="manual_suspect_id" name="manual_suspect_id" class="form-control" 
                                           value="<?= isset($vehicle['Suspect_ID']) && !in_array($vehicle['Suspect_ID'], array_column($suspects, 'Suspect_ID')) ? htmlspecialchars($vehicle['Suspect_ID']) : '' ?>">
                                    <span class="manual-suspect-toggle" onclick="toggleSuspectInput()">Or select from list</span>
                                </div>
                                
                                <?php if ($vehicle_id && !empty($vehicle['Suspect_Name'])): ?>
                                <div class="suspect-info">
                                    <strong>Currently Associated With:</strong> <?= htmlspecialchars($vehicle['Suspect_Name']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="case_details.php?id=<?= $case_id ?>" class="btn btn-secondary btn-lg px-4">
                            <i class="bi bi-arrow-left"></i> Back to Case
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-save"></i> <?= $vehicle_id ? 'Update' : 'Save' ?> Vehicle
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
        
        // Toggle between dropdown and manual suspect ID input
        function toggleSuspectInput() {
            const selectContainer = document.getElementById('suspect-select-container');
            const manualContainer = document.getElementById('manual-suspect-container');
            
            if (selectContainer.style.display === 'none') {
                selectContainer.style.display = 'block';
                manualContainer.style.display = 'none';
                document.getElementById('suspect_id').value = '';
            } else {
                selectContainer.style.display = 'none';
                manualContainer.style.display = 'block';
                document.getElementById('manual_suspect_id').value = '';
            }
        }
        
        // Initialize view based on current suspect ID
        document.addEventListener('DOMContentLoaded', function() {
            const currentSuspectId = <?= isset($vehicle['Suspect_ID']) ? $vehicle['Suspect_ID'] : 'null' ?>;
            const suspectIds = [<?= implode(',', array_column($suspects, 'Suspect_ID')) ?>];
            
            if (currentSuspectId && !suspectIds.includes(currentSuspectId)) {
                toggleSuspectInput();
                document.getElementById('manual_suspect_id').value = currentSuspectId;
            }
        });
    </script>
</body>
</html>