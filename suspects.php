<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle case search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where = '';
$params = [];

if (!empty($search)) {
    $where = "WHERE c.Crime_Type LIKE ? OR cs.status_name LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Get cases with status names
$stmt = $pdo->prepare("
    SELECT c.*, cs.status_name 
    FROM kcases_id c
    JOIN case_statuses cs ON c.status_id = cs.status_id
    $where
    ORDER BY c.Created_At DESC
");
$stmt->execute($params);
$cases = $stmt->fetchAll();

// Get case statuses for filter dropdown
$stmt = $pdo->query("SELECT * FROM case_statuses");
$statuses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        <?php include 'styles.css'; ?>
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-shield-alt"></i> Police Portal</h2>
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="nav-item active"><a href="cases.php"><i class="fas fa-folder-open"></i> Cases</a></li>
            <li class="nav-item"><a href="suspects.php"><i class="fas fa-user-secret"></i> Suspects</a></li>
            <li class="nav-item"><a href="evidence.php"><i class="fas fa-camera"></i> Evidence</a></li>
            <li class="nav-item"><a href="patrol.php"><i class="fas fa-map-marked-alt"></i> Patrol Zones</a></li>
            <li class="nav-item"><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="container">
            <h1><i class="fas fa-folder-open"></i> Case Management</h1>
            
            <div class="controls">
                <div class="search-container">
                    <form method="GET" action="cases.php">
                        <input type="text" name="search" placeholder="Search cases..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" id="searchBtn"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <button id="addCaseBtn" onclick="window.location.href='add_case.php'"><i class="fas fa-plus"></i> Add New Case</button>
            </div>
            
            <table id="casesTable">
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>Crime Type</th>
                        <th>Status</th>
                        <th>Date Opened</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cases as $case): ?>
                        <tr>
                            <td><?php echo $case['Cases_ID']; ?></td>
                            <td><?php echo htmlspecialchars($case['Crime_Type']); ?></td>
                            <td><span class="status-<?php echo strtolower($case['status_name']); ?>"><?php echo $case['status_name']; ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($case['Created_At'])); ?></td>
                            <td>
                                <a href="case_details.php?id=<?php echo $case['Cases_ID']; ?>" class="btn-view">View</a>
                                <a href="edit_case.php?id=<?php echo $case['Cases_ID']; ?>" class="btn-edit">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>