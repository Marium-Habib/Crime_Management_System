<?php
ob_start();
require_once 'db_config.php';
require_once 'auth.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$case_id = $_GET['case_id'] ?? null;
$imprisonment_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$case_id || !is_numeric($case_id)) {
    header("Location: lawyer_dashboard.php");
    exit();
}

// Check if user has access to this case
$stmt = $pdo->prepare("SELECT COUNT(*) FROM lawyers_id WHERE User_ID = ? AND Cases_ID = ?");
$stmt->execute([$user_id, $case_id]);
if ($stmt->fetchColumn() == 0) {
    header("Location: unauthorized.php");
    exit();
}

$error = '';
$imprisonment = null;
$suspects = [];

// Get all suspects for this case
try {
    $stmt = $pdo->prepare("SELECT Suspect_ID, Name FROM xsuspect_id WHERE Cases_ID = ?");
    $stmt->execute([$case_id]);
    $suspects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching suspects: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $suspect_id = (int)$_POST['suspect_id'];
        $prison_name = htmlspecialchars(trim($_POST['prison_name']));
        $start_date = $_POST['start_date'] ?? date('Y-m-d');
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $details = htmlspecialchars(trim($_POST['details'] ?? ''));

        // Validate dates
        if ($end_date && strtotime($end_date) < strtotime($start_date)) {
            throw new Exception("End date cannot be before start date");
        }

        if ($imprisonment_id) {
            // Update existing record
            $stmt = $pdo->prepare("UPDATE imprisonment SET 
                                 Suspect_ID = ?, 
                                 Prison_Name = ?,
                                 Start_Date = ?,
                                 End_Date = ?,
                                 Details = ?,
                                 Recorded_By = ?,
                                 Cases_ID = ?
                                 WHERE Imprisonment_ID = ?");
            $stmt->execute([
                $suspect_id, 
                $prison_name, 
                $start_date, 
                $end_date, 
                $details, 
                $user_id,
                $case_id,
                $imprisonment_id
            ]);
            
            $success_message = "Imprisonment record updated successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        } else {
            // Create new record
            $stmt = $pdo->prepare("INSERT INTO imprisonment 
                                 (Suspect_ID, Prison_Name, Start_Date, End_Date, Details, Recorded_By, Cases_ID) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $suspect_id, 
                $prison_name, 
                $start_date, 
                $end_date, 
                $details, 
                $user_id,
                $case_id
            ]);
            
            $success_message = "Imprisonment record added successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Load existing record if editing
if ($imprisonment_id) {
    try {
        $stmt = $pdo->prepare("SELECT i.*, u.Full_Name as Recorded_By_Name, s.Name as Suspect_Name
                              FROM imprisonment i
                              JOIN user_id u ON i.Recorded_By = u.User_ID
                              JOIN xsuspect_id s ON i.Suspect_ID = s.Suspect_ID
                              WHERE Imprisonment_ID = ? AND i.Cases_ID = ?");
        $stmt->execute([$imprisonment_id, $case_id]);
        $imprisonment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$imprisonment) {
            header("Location: case_details.php?id=$case_id&error=".urlencode("Imprisonment record not found"));
            exit();
        }
    } catch (PDOException $e) {
        $error = "Error loading record: " . $e->getMessage();
    }
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $imprisonment_id ? 'Edit' : 'Add' ?> Imprisonment Record</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--primary-brown);
            border-color: var(--primary-brown);
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: var(--dark-brown);
            border-color: var(--dark-brown);
        }
        
        .btn-secondary {
            background-color: var(--light-brown);
            border-color: var(--light-brown);
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }
        
        .btn-secondary:hover {
            background-color: var(--primary-brown);
            border-color: var(--primary-brown);
        }
        
        .form-control:focus, .form-select:focus {
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
            font-size: 0.9rem;
            font-weight: 500;
            padding: 0.5rem 0.8rem;
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .recorded-by {
            background-color: rgba(239, 235, 233, 0.7);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid var(--primary-brown);
            font-size: 0.95rem;
        }
        
        .section-header {
            color: var(--primary-brown); 
            border-bottom: 1px solid var(--lighter-brown); 
            padding-bottom: 8px;
            margin: 1.5rem 0 1rem;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark-brown);
        }
        
        .invalid-feedback {
            font-size: 0.85rem;
        }
        
        .alert {
            border-radius: 8px;
        }
        
        .suspect-info {
            font-size: 0.9rem;
            color: var(--light-brown);
            margin-top: 0.25rem;
        }
        
        .suspect-photo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--lighter-brown);
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="bi bi-shield-lock"></i> <?= $imprisonment_id ? 'Edit' : 'Add' ?> Imprisonment Record</h2>
                    <span class="badge badge-case">Case ID: <?= htmlspecialchars($case_id) ?></span>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($imprisonment_id && isset($imprisonment['Recorded_By_Name'])): ?>
                    <div class="recorded-by">
                        <div class="d-flex align-items-center">
                            <?php if (isset($imprisonment['Suspect_Name'])): ?>
                                <div class="me-3">
                                    <img src="assets/default-suspect.png" alt="Suspect" class="suspect-photo">
                                </div>
                            <?php endif; ?>
                            <div>
                                <strong>Recorded By:</strong> <?= htmlspecialchars($imprisonment['Recorded_By_Name']) ?>
                                <?php if (isset($imprisonment['Suspect_Name'])): ?>
                                    <br>
                                    <strong>Suspect:</strong> <?= htmlspecialchars($imprisonment['Suspect_Name']) ?>
                                <?php endif; ?>
                                <br>
                                <small class="text-muted">on <?= date('M d, Y h:i A', strtotime($imprisonment['Start_Date'])) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-person-lines-fill"></i> Suspect Information
                        </h5>
                        
                        <div class="mb-3">
                            <label for="suspect_id" class="form-label required">Select Suspect</label>
                            <select id="suspect_id" name="suspect_id" class="form-select" required>
                                <option value="">-- Select Suspect --</option>
                                <?php foreach ($suspects as $suspect): ?>
                                    <option value="<?= $suspect['Suspect_ID'] ?>" 
                                        <?= isset($imprisonment['Suspect_ID']) && $imprisonment['Suspect_ID'] == $suspect['Suspect_ID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($suspect['Name']) ?> (ID: <?= $suspect['Suspect_ID'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a suspect</div>
                            <?php if (empty($suspects)): ?>
                                <div class="alert alert-warning mt-2">
                                    No suspects found for this case. Please add suspects first.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-building-lock"></i> Prison Information
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="prison_name" class="form-label required">Prison Name</label>
                                <input type="text" id="prison_name" name="prison_name" class="form-control" 
                                       value="<?= htmlspecialchars($imprisonment['Prison_Name'] ?? '') ?>" required>
                                <div class="invalid-feedback">Please enter prison name</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="start_date" class="form-label required">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" 
                                       value="<?= isset($imprisonment['Start_Date']) ? date('Y-m-d', strtotime($imprisonment['Start_Date'])) : date('Y-m-d') ?>" required>
                                <div class="invalid-feedback">Please select start date</div>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date (if applicable)</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" 
                                       value="<?= isset($imprisonment['End_Date']) ? date('Y-m-d', strtotime($imprisonment['End_Date'])) : '' ?>"
                                       min="<?= isset($imprisonment['Start_Date']) ? date('Y-m-d', strtotime($imprisonment['Start_Date'])) : date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-card-text"></i> Additional Details
                        </h5>
                        
                        <div class="mb-3">
                            <label for="details" class="form-label">Details</label>
                            <textarea id="details" name="details" class="form-control" rows="5"
                                      placeholder="Additional details about the imprisonment"><?= htmlspecialchars($imprisonment['Details'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="case_details.php?id=<?= $case_id ?>" class="btn btn-secondary btn-lg px-4">
                            <i class="bi bi-arrow-left"></i> Back to Case
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-4" <?= empty($suspects) ? 'disabled' : '' ?>>
                            <i class="bi bi-save"></i> <?= $imprisonment_id ? 'Update' : 'Save' ?> Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            'use strict'
            
            // Form validation
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
            
            // Set minimum end date based on start date
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            
            if (startDate && endDate) {
                startDate.addEventListener('change', function() {
                    endDate.min = this.value;
                    if (endDate.value && endDate.value < this.value) {
                        endDate.value = '';
                    }
                });
            }
        })()
    </script>
</body>
</html>