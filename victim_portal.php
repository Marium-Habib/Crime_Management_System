<?php
session_start(); // THIS WAS MISSING - CRITICAL
require_once 'db_config_victim.php';

// Fixed session check - consistent with login.php
if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'victim') {
    header("Location: victim_login.php"); // Make sure this matches your login file name
    exit();
}

$stmt = $db->prepare("
    SELECT v.Victim_ID, v.Name, k.Cases_ID, k.Crime_Type, cs.status_name AS case_status, 
           k.Created_At AS case_opened_date, v.Statement
    FROM victim v
    JOIN kcases_id k ON v.Cases_ID = k.Cases_ID
    JOIN case_statuses cs ON k.status_id = cs.status_id
    WHERE v.User_ID = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Victim Portal</title>
    <link rel="stylesheet" href="victim_styles.css">
</head>
<body>
    <div class="header">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></h1>
        <a href="victim_logout.php" class="logout-btn">Logout</a>
    </div>
    
    <div class="container">
        <h2>Your Cases</h2>
        
        <?php if(empty($cases)): ?>
            <p>No cases found.</p>
        <?php else: ?>
            <?php foreach($cases as $case): ?>
                <div class="case-card">
                    <h3>Case #<?= $case['Cases_ID'] ?>: <?= $case['Crime_Type'] ?></h3>
                    <p>Status: <span class="status-<?= strtolower($case['case_status']) ?>">
                        <?= $case['case_status'] ?>
                    </span></p>
                    <p>Your Statement: <?= nl2br(htmlspecialchars($case['Statement'])) ?></p>
                    <a href="victim_case_details.php?case_id=<?= $case['Cases_ID'] ?>" class="btn">View Details</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>