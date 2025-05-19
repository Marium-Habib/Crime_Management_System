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

// Get case and judgement IDs
$case_id = $_GET['case_id'] ?? null;
$judgement_id = $_GET['id'] ?? null;

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
$judgement = null;
$court = null;

// Fetch court information for this case
$stmt = $pdo->prepare("SELECT * FROM court WHERE Cases_ID = ?");
$stmt->execute([$case_id]);
$court = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch existing judgement if editing
if ($judgement_id) {
    $stmt = $pdo->prepare("SELECT j.*, c.Judge_Name, c.Court_Name 
                          FROM judgement j
                          JOIN court c ON j.Court_ID = c.Court_ID
                          WHERE j.Judgement_ID = ? AND j.Cases_ID = ?");
    $stmt->execute([$judgement_id, $case_id]);
    $judgement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$judgement) {
        header("Location: case_details.php?id=$case_id");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize inputs
        $verdict = htmlspecialchars(trim($_POST['verdict']));
        $sentence = htmlspecialchars(trim($_POST['sentence'] ?? ''));
        $details = htmlspecialchars(trim($_POST['details'] ?? ''));
        $judgement_date = $_POST['judgement_date'] ?? date('Y-m-d H:i:s');
        $court_id = $court['Court_ID'];

        if ($judgement_id) {
            // Update existing judgement
            $stmt = $pdo->prepare("UPDATE judgement SET 
                                 Verdict = ?, 
                                 Sentence = ?,
                                 Details = ?,
                                 Judgement_Date = ?
                                 WHERE Judgement_ID = ?");
            $stmt->execute([$verdict, $sentence, $details, $judgement_date, $judgement_id]);
            
            $success_message = "Judgement updated successfully!";
        } else {
            // Create new judgement
            $stmt = $pdo->prepare("INSERT INTO judgement 
                                 (Verdict, Sentence, Details, Judgement_Date, Court_ID, Cases_ID) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$verdict, $sentence, $details, $judgement_date, $court_id, $case_id]);
            
            $success_message = "Judgement recorded successfully!";
        }
        
        $redirect_url = "case_details.php?id=$case_id";
        header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
        exit();
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch all judgements for this case to show in a list
$stmt = $pdo->prepare("SELECT * FROM judgement WHERE Cases_ID = ? ORDER BY Judgement_Date DESC");
$stmt->execute([$case_id]);
$all_judgements = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $judgement_id ? 'Edit' : 'Add' ?> Judgement</title>
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
        
        .court-info {
            background-color: var(--cream);
            padding: 15px;
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
        
        .verdict-options {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .verdict-option {
            flex: 1;
            min-width: 120px;
        }
        
        .verdict-option input[type="radio"] {
            display: none;
        }
        
        .verdict-option label {
            display: block;
            padding: 10px 15px;
            border: 2px solid var(--lighter-brown);
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .verdict-option input[type="radio"]:checked + label {
            background-color: var(--primary-brown);
            color: white;
            border-color: var(--primary-brown);
        }
        
        .judgement-list {
            margin-bottom: 30px;
        }
        
        .judgement-item {
            padding: 15px;
            border: 1px solid var(--lighter-brown);
            border-radius: 5px;
            margin-bottom: 10px;
            background-color: white;
        }
        
        .judgement-item-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .judgement-verdict {
            font-weight: bold;
            color: var(--primary-brown);
        }
        
        .judgement-date {
            color: var(--light-brown);
            font-size: 0.9rem;
        }
        
        .add-new-btn {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="bi bi-gavel"></i> Case Judgements</h2>
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
                
                <?php if ($court): ?>
                    <div class="court-info">
                        <h5><i class="bi bi-building"></i> Court Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Court:</strong> <?= htmlspecialchars($court['Court_Name']) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Judge:</strong> <?= htmlspecialchars($court['Judge_Name']) ?>
                            </div>
                        </div>
                        <div class="mt-2">
                            <strong>Address:</strong> <?= htmlspecialchars($court['Address']) ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- List of existing judgements -->
                <div class="judgement-list">
                    <h5 class="section-header">
                        <i class="bi bi-list-check"></i> Recorded Judgements
                    </h5>
                    
                    <?php if (empty($all_judgements)): ?>
                        <div class="alert alert-info">
                            No judgements recorded yet for this case.
                        </div>
                    <?php else: ?>
                        <?php foreach ($all_judgements as $j): ?>
                            <div class="judgement-item">
                                <div class="judgement-item-header">
                                    <span class="judgement-verdict">
                                        <?= htmlspecialchars($j['Verdict']) ?>
                                        <?php if ($j['Sentence']): ?>
                                            - <?= htmlspecialchars($j['Sentence']) ?>
                                        <?php endif; ?>
                                    </span>
                                    <span class="judgement-date">
                                        <?= date('M d, Y h:i A', strtotime($j['Judgement_Date'])) ?>
                                    </span>
                                </div>
                                <?php if ($j['Details']): ?>
                                    <div class="judgement-details">
                                        <?= nl2br(htmlspecialchars($j['Details'])) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-2 text-end">
                                    <a href="manage_judgements.php?case_id=<?= $case_id ?>&id=<?= $j['Judgement_ID'] ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                <!-- Judgement Form (shown when adding/editing) -->
                <?php if ($judgement_id || !$all_judgements || isset($_GET['add'])): ?>
                    <form method="POST" class="needs-validation" novalidate>
                        <h5 class="section-header">
                            <i class="bi bi-clipboard2-check"></i> <?= $judgement_id ? 'Edit Judgement' : 'Add New Judgement' ?>
                        </h5>
                        
                        <div class="mb-4">
                            <h6 class="section-header">
                                <i class="bi bi-clipboard2-check"></i> Verdict
                            </h6>
                            
                            <div class="verdict-options mb-3">
                                <div class="verdict-option">
                                    <input type="radio" id="verdict-guilty" name="verdict" value="Guilty" 
                                           <?= ($judgement['Verdict'] ?? '') === 'Guilty' ? 'checked' : '' ?> required>
                                    <label for="verdict-guilty">Guilty</label>
                                </div>
                                <div class="verdict-option">
                                    <input type="radio" id="verdict-not-guilty" name="verdict" value="Not Guilty" 
                                           <?= ($judgement['Verdict'] ?? '') === 'Not Guilty' ? 'checked' : '' ?>>
                                    <label for="verdict-not-guilty">Not Guilty</label>
                                </div>
                                <div class="verdict-option">
                                    <input type="radio" id="verdict-dismissed" name="verdict" value="Dismissed" 
                                           <?= ($judgement['Verdict'] ?? '') === 'Dismissed' ? 'checked' : '' ?>>
                                    <label for="verdict-dismissed">Dismissed</label>
                                </div>
                                <div class="verdict-option">
                                    <input type="radio" id="verdict-other" name="verdict" value="Other" 
                                           <?= isset($judgement['Verdict']) && !in_array($judgement['Verdict'], ['Guilty', 'Not Guilty', 'Dismissed']) ? 'checked' : '' ?>>
                                    <label for="verdict-other">Other</label>
                                </div>
                            </div>
                            <div class="invalid-feedback">Please select a verdict</div>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="section-header">
                                <i class="bi bi-clock-history"></i> Judgement Details
                            </h6>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="judgement_date" class="form-label required">Judgement Date</label>
                                    <input type="datetime-local" id="judgement_date" name="judgement_date" class="form-control" 
                                           value="<?= isset($judgement['Judgement_Date']) ? date('Y-m-d\TH:i', strtotime($judgement['Judgement_Date'])) : date('Y-m-d\TH:i') ?>" required>
                                    <div class="invalid-feedback">Please select a valid date</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="sentence" class="form-label">Sentence</label>
                                    <input type="text" id="sentence" name="sentence" class="form-control" 
                                           value="<?= htmlspecialchars($judgement['Sentence'] ?? '') ?>"
                                           placeholder="e.g., 5 years imprisonment, fine, etc.">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="section-header">
                                <i class="bi bi-file-text"></i> Additional Details
                            </h6>
                            
                            <div class="mb-3">
                                <label for="details" class="form-label">Judgement Details</label>
                                <textarea id="details" name="details" class="form-control" rows="5"
                                          placeholder="Full details of the judgement, including any special considerations"><?= htmlspecialchars($judgement['Details'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="case_details.php?case_id=<?= $case_id ?>" class="btn btn-secondary btn-lg px-4">
                                <i class="bi bi-arrow-left"></i> Back to Case
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="bi bi-save"></i> <?= $judgement_id ? 'Update' : 'Save' ?> Judgement
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
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
        
        // Enable custom verdict input if "Other" is selected
        document.addEventListener('DOMContentLoaded', function() {
            const otherRadio = document.getElementById('verdict-other');
            const verdictOptions = document.querySelectorAll('input[name="verdict"]');
            
            verdictOptions.forEach(option => {
                option.addEventListener('change', function() {
                    if (this.id === 'verdict-other' && this.checked) {
                        const customVerdict = prompt('Please specify the verdict:');
                        if (customVerdict) {
                            this.value = customVerdict;
                        } else {
                            // Reset to another option if user cancels
                            document.getElementById('verdict-guilty').checked = true;
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>