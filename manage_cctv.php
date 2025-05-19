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

// Get case and CCTV IDs
$case_id = $_GET['case_id'] ?? null;
$cctv_id = $_GET['id'] ?? null;

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
$cctv = null;
$file_error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize inputs
        $location = htmlspecialchars(trim($_POST['location']));
        $timestamp = $_POST['timestamp'] ?? date('Y-m-d H:i:s');
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        
        // Handle file upload
        $file_path = null;
        if (isset($_FILES['cctv_file']) && $_FILES['cctv_file']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/cctv_evidence/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = basename($_FILES['cctv_file']['name']);
            $target_path = $upload_dir . uniqid() . '_' . $file_name;
            
            // Validate file type
            $allowed_types = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'image/jpeg', 'image/png'];
            $file_type = $_FILES['cctv_file']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                if (move_uploaded_file($_FILES['cctv_file']['tmp_name'], $target_path)) {
                    $file_path = $target_path;
                } else {
                    $file_error = "Failed to upload file.";
                }
            } else {
                $file_error = "Invalid file type. Only video (MP4, MOV, AVI, WMV) and image (JPEG, PNG) files are allowed.";
            }
        } elseif (isset($_FILES['cctv_file']) && $_FILES['cctv_file']['error'] != UPLOAD_ERR_NO_FILE) {
            $file_error = "File upload error: " . $_FILES['cctv_file']['error'];
        }

        if ($cctv_id) {
            // Update existing CCTV evidence
            if ($file_path) {
                // Delete old file if exists
                if (!empty($cctv['File_Path']) && file_exists($cctv['File_Path'])) {
                    unlink($cctv['File_Path']);
                }
                
                $stmt = $pdo->prepare("UPDATE cctv_evidence SET 
                                     Location = ?, 
                                     Timestamp = ?,
                                     Description = ?,
                                     File_Path = ?
                                     WHERE CCTV_ID = ? AND Cases_ID = ?");
                $stmt->execute([$location, $timestamp, $description, $file_path, $cctv_id, $case_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE cctv_evidence SET 
                                     Location = ?, 
                                     Timestamp = ?,
                                     Description = ?
                                     WHERE CCTV_ID = ? AND Cases_ID = ?");
                $stmt->execute([$location, $timestamp, $description, $cctv_id, $case_id]);
            }
            
            $success_message = "CCTV evidence updated successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        } else {
            // Create new CCTV evidence
            $stmt = $pdo->prepare("INSERT INTO cctv_evidence 
                                 (Location, Timestamp, Description, File_Path, Cases_ID) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$location, $timestamp, $description, $file_path, $case_id]);
            
            $cctv_id = $pdo->lastInsertId();
            $success_message = "CCTV evidence added successfully!";
            $redirect_url = "case_details.php?id=$case_id";
            header("Location: success_message.php?message=".urlencode($success_message)."&redirect=".urlencode($redirect_url));
            exit();
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch CCTV data if editing
if ($cctv_id) {
    $stmt = $pdo->prepare("SELECT * FROM cctv_evidence WHERE CCTV_ID = ? AND Cases_ID = ?");
    $stmt->execute([$cctv_id, $case_id]);
    $cctv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cctv) {
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
    <title><?= $cctv_id ? 'Edit' : 'Add' ?> CCTV Evidence</title>
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
        
        .media-preview {
            max-width: 100%;
            max-height: 300px;
            margin-top: 15px;
            border-radius: 5px;
            border: 1px solid var(--lighter-brown);
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="bi bi-camera-video-fill"></i> <?= $cctv_id ? 'Edit CCTV Evidence' : 'Add New CCTV Evidence' ?></h2>
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
                            <i class="bi bi-card-checklist"></i> CCTV Details
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="location" class="form-label required">Location</label>
                                <input type="text" id="location" name="location" class="form-control" 
                                       value="<?= htmlspecialchars($cctv['Location'] ?? '') ?>" required>
                                <div class="invalid-feedback">Please enter location</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="timestamp" class="form-label required">Timestamp</label>
                                <input type="datetime-local" id="timestamp" name="timestamp" class="form-control" 
                                       value="<?= isset($cctv['Timestamp']) ? date('Y-m-d\TH:i', strtotime($cctv['Timestamp'])) : date('Y-m-d\TH:i') ?>" required>
                                <div class="invalid-feedback">Please select timestamp</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-card-text"></i> Description
                        </h5>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="5"
                                      placeholder="Description of what the CCTV footage shows"><?= htmlspecialchars($cctv['Description'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-file-earmark-arrow-up"></i> CCTV File
                        </h5>
                        
                        <div class="mb-3">
                            <label for="cctv_file" class="form-label"><?= $cctv_id ? 'Replace CCTV File' : 'Upload CCTV File' ?></label>
                            <input type="file" id="cctv_file" name="cctv_file" class="form-control">
                            <small class="text-muted">Video (MP4, MOV, AVI, WMV) or image (JPEG, PNG) files only (Max 50MB)</small>
                            
                            <?php if ($file_error): ?>
                                <div class="alert alert-danger mt-2">
                                    <i class="bi bi-exclamation-triangle-fill"></i> <?= $file_error ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($cctv_id && !empty($cctv['File_Path'])): ?>
                                <div class="file-preview mt-2">
                                    <strong>Current File:</strong> 
                                    <a href="<?= htmlspecialchars($cctv['File_Path']) ?>" target="_blank">
                                        <i class="bi bi-file-earmark"></i> View CCTV Evidence
                                    </a>
                                    <?php if (strpos($cctv['File_Path'], '.mp4') !== false || strpos($cctv['File_Path'], '.mov') !== false || 
                                              strpos($cctv['File_Path'], '.avi') !== false || strpos($cctv['File_Path'], '.wmv') !== false): ?>
                                        <video controls class="media-preview">
                                            <source src="<?= htmlspecialchars($cctv['File_Path']) ?>" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php else: ?>
                                        <img src="<?= htmlspecialchars($cctv['File_Path']) ?>" alt="CCTV Image" class="media-preview">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="case_details.php?id=<?= $case_id ?>" class="btn btn-secondary btn-lg px-4">
                            <i class="bi bi-arrow-left"></i> Back to Case
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-save"></i> <?= $cctv_id ? 'Update' : 'Save' ?> CCTV Evidence
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
        
        // Preview file before upload
        document.getElementById('cctv_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const previewContainer = document.querySelector('.file-preview');
            if (!previewContainer) return;
            
            const fileType = file.type;
            const videoTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv'];
            const imageTypes = ['image/jpeg', 'image/png'];
            
            if (videoTypes.includes(fileType)) {
                const videoPreview = document.createElement('video');
                videoPreview.controls = true;
                videoPreview.className = 'media-preview';
                videoPreview.innerHTML = 'Your browser does not support the video tag.';
                
                const source = document.createElement('source');
                source.src = URL.createObjectURL(file);
                source.type = fileType;
                
                videoPreview.appendChild(source);
                previewContainer.appendChild(videoPreview);
            } else if (imageTypes.includes(fileType)) {
                const imgPreview = document.createElement('img');
                imgPreview.src = URL.createObjectURL(file);
                imgPreview.className = 'media-preview';
                imgPreview.alt = 'Selected CCTV File';
                previewContainer.appendChild(imgPreview);
            }
        });
    </script>
</body>
</html>