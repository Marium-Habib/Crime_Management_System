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
$document_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$case_id || !is_numeric($case_id)) {
    header("Location: lawyer_dashboard.php");
    exit();
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM lawyers_id WHERE User_ID = ? AND Cases_ID = ?");
$stmt->execute([$user_id, $case_id]);
if ($stmt->fetchColumn() == 0) {
    header("Location: unauthorized.php");
    exit();
}

$error = '';
$document = null;
$allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $document_type = htmlspecialchars(trim($_POST['document_type']));
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        
        // Handle file upload
        $file_path = null;
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $file_info = $_FILES['document_file'];
            
            // Validate file type
            if (!in_array($file_info['type'], $allowed_types)) {
                throw new Exception("Invalid file type. Only PDF, JPEG, PNG, and Word documents are allowed.");
            }
            
            // Create uploads directory if it doesn't exist
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $target_path = 'uploads/' . $filename;
            
            if (move_uploaded_file($file_info['tmp_name'], $target_path)) {
                $file_path = $target_path;
            } else {
                throw new Exception("Failed to upload file.");
            }
        } elseif ($document_id && empty($_FILES['document_file']['name'])) {
            // Keep existing file if editing and no new file uploaded
            $file_path = $document['File_Path'] ?? null;
        } else {
            throw new Exception("Please select a file to upload.");
        }

        if ($document_id) {
            // Update existing document
            $stmt = $pdo->prepare("UPDATE case_document SET 
                                 Document_Type = ?, 
                                 File_Path = ?,
                                 Description = ?,
                                 Uploaded_By = ?
                                 WHERE Document_ID = ?");
            $stmt->execute([$document_type, $file_path, $description, $user_id, $document_id]);
            
            $success_message = "Document updated successfully!";
        } else {
            // Create new document
            $stmt = $pdo->prepare("INSERT INTO case_document 
                                 (Document_Type, File_Path, Description, Uploaded_By, Cases_ID) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$document_type, $file_path, $description, $case_id, $case_id]);
            
            $success_message = "Document added successfully!";
        }
        
        header("Location: success_message.php?message=".urlencode($success_message)."&redirect=case_details.php?id=$case_id");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

if ($document_id) {
    $stmt = $pdo->prepare("SELECT d.*, u.Full_Name as Uploaded_By_Name 
                          FROM case_document d
                          JOIN user_id u ON d.Uploaded_By = u.User_ID
                          WHERE d.Document_ID = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document || $document['Cases_ID'] != $case_id) {
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
    <title><?= $document_id ? 'Edit' : 'Add' ?> Case Document</title>
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
        
        .uploaded-by {
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
        
        .file-info {
            font-size: 0.9rem;
            color: var(--light-brown);
            margin-top: 0.5rem;
        }
        
        .file-preview {
            max-width: 100%;
            margin-top: 10px;
            border: 1px solid var(--lighter-brown);
            border-radius: 5px;
            padding: 5px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="bi bi-file-earmark-text"></i> <?= $document_id ? 'Edit' : 'Add' ?> Case Document</h2>
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
                
                <?php if ($document_id && isset($document['Uploaded_By_Name'])): ?>
                    <div class="uploaded-by">
                        <strong>Uploaded By:</strong> <?= htmlspecialchars($document['Uploaded_By_Name']) ?>
                        <br>
                        <small class="text-muted">on <?= date('M d, Y h:i A', strtotime($document['Upload_Date'])) ?></small>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-card-heading"></i> Document Information
                        </h5>
                        
                        <div class="mb-3">
                            <label for="document_type" class="form-label required">Document Type</label>
                            <input type="text" id="document_type" name="document_type" class="form-control" 
                                   value="<?= htmlspecialchars($document['Document_Type'] ?? '') ?>" required>
                            <div class="invalid-feedback">Please enter document type</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="document_file" class="form-label <?= !$document_id ? 'required' : '' ?>">
                                <?= $document_id ? 'Replace Document' : 'Upload Document' ?>
                            </label>
                            <input type="file" id="document_file" name="document_file" class="form-control" <?= !$document_id ? 'required' : '' ?>>
                            <div class="invalid-feedback">Please select a file to upload</div>
                            <div class="file-info">
                                Allowed file types: PDF, JPEG, PNG, Word documents
                            </div>
                            <?php if ($document_id && $document['File_Path']): ?>
                                <div class="mt-2">
                                    <p>Current file: 
                                        <a href="<?= htmlspecialchars($document['File_Path']) ?>" target="_blank">
                                            <?= basename($document['File_Path']) ?>
                                        </a>
                                    </p>
                                    <?php if (strpos($document['File_Path'], '.pdf') !== false): ?>
                                        <embed src="<?= htmlspecialchars($document['File_Path']) ?>" type="application/pdf" width="100%" height="300px" class="file-preview">
                                    <?php elseif (strpos($document['File_Path'], '.jpg') !== false || strpos($document['File_Path'], '.jpeg') !== false || strpos($document['File_Path'], '.png') !== false): ?>
                                        <img src="<?= htmlspecialchars($document['File_Path']) ?>" alt="Document Preview" class="file-preview" style="max-height: 300px;">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="section-header">
                            <i class="bi bi-card-text"></i> Description
                        </h5>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="5"
                                      placeholder="Description of the document"><?= htmlspecialchars($document['Description'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="case_details.php?id=<?= $case_id ?>" class="btn btn-secondary btn-lg px-4">
                            <i class="bi bi-arrow-left"></i> Back to Case
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-save"></i> <?= $document_id ? 'Update' : 'Save' ?> Document
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