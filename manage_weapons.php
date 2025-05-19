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

// Get case and weapon IDs
$case_id = $_GET['case_id'] ?? null;
$weapon_id = $_GET['id'] ?? null;

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
$weapon = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize inputs
        $type = htmlspecialchars(trim($_POST['type']));
        $serial_number = htmlspecialchars(trim($_POST['serial_number'] ?? ''));
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $status = htmlspecialchars(trim($_POST['status']));
        
        // Properly handle suspect_id - convert empty string to NULL
        $suspect_id = !empty($_POST['suspect_id']) ? (int)$_POST['suspect_id'] : null;

        if ($weapon_id) {
            // Update existing weapon
            $stmt = $pdo->prepare("UPDATE weapon SET 
                                 Type = ?, 
                                 Serial_Number = ?,
                                 Description = ?,
                                 Status = ?,
                                 Suspect_ID = ?
                                 WHERE Weapon_ID = ? AND Cases_ID = ?");
            $stmt->execute([$type, $serial_number, $description, $status, $suspect_id, $weapon_id, $case_id]);
            
            $success_message = "Weapon updated successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        } else {
            // Create new weapon
            $stmt = $pdo->prepare("INSERT INTO weapon 
                                 (Type, Serial_Number, Description, Status, Cases_ID, Suspect_ID) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$type, $serial_number, $description, $status, $case_id, $suspect_id]);
            
            $weapon_id = $pdo->lastInsertId();
            $success_message = "Weapon added successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch weapon data if editing
if ($weapon_id) {
    $stmt = $pdo->prepare("SELECT * FROM weapon WHERE Weapon_ID = ? AND Cases_ID = ?");
    $stmt->execute([$weapon_id, $case_id]);
    $weapon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$weapon) {
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
    <title><?= $weapon_id ? 'Edit' : 'Add' ?> Weapon</title>
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
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="bi bi-shield-fill-exclamation"></i> <?= $weapon_id ? 'Edit Weapon' : 'Add New Weapon' ?></h2>
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
                            <i class="bi bi-card-checklist"></i> Weapon Details
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label required">Weapon Type</label>
                                <select id="type" name="type" class="form-select form-select-lg" required>
                                    <option value="">Select type</option>
                                    <option value="Firearm" <?= isset($weapon['Type']) && $weapon['Type'] === 'Firearm' ? 'selected' : '' ?>>Firearm</option>
                                    <option value="Knife" <?= isset($weapon['Type']) && $weapon['Type'] === 'Knife' ? 'selected' : '' ?>>Knife</option>
                                    <option value="Blunt Object" <?= isset($weapon['Type']) && $weapon['Type'] === 'Blunt Object' ? 'selected' : '' ?>>Blunt Object</option>
                                    <option value="Chemical" <?= isset($weapon['Type']) && $weapon['Type'] === 'Chemical' ? 'selected' : '' ?>>Chemical</option>
                                    <option value="Explosive" <?= isset($weapon['Type']) && $weapon['Type'] === 'Explosive' ? 'selected' : '' ?>>Explosive</option>
                                    <option value="Other" <?= isset($weapon['Type']) && $weapon['Type'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                                <div class="invalid-feedback">Please select weapon type</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="status" class="form-label required">Status</label>
                                <select id="status" name="status" class="form-select form-select-lg" required>
                                    <option value="">Select status</option>
                                    <option value="recovered" <?= isset($weapon['Status']) && $weapon['Status'] === 'recovered' ? 'selected' : '' ?>>Recovered</option>
                                    <option value="missing" <?= isset($weapon['Status']) && $weapon['Status'] === 'missing' ? 'selected' : '' ?>>Missing</option>
                                </select>
                                <div class="invalid-feedback">Please select weapon status</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="serial_number" class="form-label">Serial Number</label>
                                <input type="text" id="serial_number" name="serial_number" class="form-control" 
                                       value="<?= htmlspecialchars($weapon['Serial_Number'] ?? '') ?>"
                                       placeholder="Weapon serial number if available">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="suspect_id" class="form-label">Associated Suspect</label>
                                <select id="suspect_id" name="suspect_id" class="form-select form-select-lg">
                                    <option value="">No suspect assigned</option>
                                    <?php foreach ($suspects as $suspect): ?>
                                        <option value="<?= $suspect['Suspect_ID'] ?>" 
                                            <?= isset($weapon['Suspect_ID']) && $weapon['Suspect_ID'] == $suspect['Suspect_ID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($suspect['Name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
                                      placeholder="Detailed description of the weapon including any identifying marks or features"><?= htmlspecialchars($weapon['Description'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="case_details.php?id=<?= $case_id ?>" class="btn btn-secondary btn-lg px-4">
                            <i class="bi bi-arrow-left"></i> Back to Case
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-save"></i> <?= $weapon_id ? 'Update' : 'Save' ?> Weapon
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