<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'officer') {
    header("Location: police_login.php");
    exit();
}

$case_id = $_GET['case_id'] ?? '';
$error = '';
$success = '';

// Get current case status
$case = $conn->query("SELECT Status FROM kcases_id WHERE Cases_ID = $case_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE kcases_id SET Status = ? WHERE Cases_ID = ?");
    $stmt->bind_param("si", $new_status, $case_id);
    
    if ($stmt->execute()) {
        $success = "Case status updated successfully!";
        $case['Status'] = $new_status;
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Case Status</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Update Case Status #<?= htmlspecialchars($case_id) ?></h2>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
            <a href="case_details.php?case_id=<?= $case_id ?>">Back to Case</a>
        <?php else: ?>
        
        <form method="POST">
            <input type="hidden" name="case_id" value="<?= htmlspecialchars($case_id) ?>">
            
            <div class="form-group">
                <label>Current Status:</label>
                <input type="text" value="<?= htmlspecialchars($case['Status']) ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>New Status:</label>
                <select name="status" required>
                    <option value="open" <?= $case['Status'] == 'open' ? 'selected' : '' ?>>Open</option>
                    <option value="investigating" <?= $case['Status'] == 'investigating' ? 'selected' : '' ?>>Investigating</option>
                    <option value="closed" <?= $case['Status'] == 'closed' ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>
            
            <button type="submit" class="btn">Update Status</button>
            <a href="case_details.php?case_id=<?= $case_id ?>" style="margin-left: 10px;">Cancel</a>
        </form>
        
        <?php endif; ?>
    </div>
</body>
</html>