<?php
session_start();

// Initialize database connection
try {
    $db = new PDO("mysql:host=localhost;dbname=crime_management_system", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("<div class='error'>Database connection failed: " . $e->getMessage() . "</div>");
}

// Get user data directly from database (without authentication)
try {
    // Example: Get first victim user from database
    $stmt = $db->query("SELECT 
                        u.User_ID, 
                        u.user_type_id, 
                        v.Victim_ID 
                        FROM user_id u
                        JOIN victim v ON u.User_ID = v.User_ID
                        WHERE u.user_type_id = 3  /* assuming 3 is victim type */
                        LIMIT 1");
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user) {
        $_SESSION['user_id'] = $user['User_ID'];
        $_SESSION['user_type'] = 'victim'; // or map from user_type_id
        $_SESSION['victim_id'] = $user['Victim_ID'];
    } else {
        // Fallback to test data if no users found
        $_SESSION['user_id'] = 1;
        $_SESSION['user_type'] = 'victim';
        $_SESSION['victim_id'] = 1;
        echo "<div class='warning'>No victim users found in database, using test data</div>";
    }
    
} catch(PDOException $e) {
    die("<div class='error'>Database error: " . $e->getMessage() . "</div>");
}

// Get case ID from URL or default to 1 for testing
$case_id = filter_input(INPUT_GET, 'case_id', FILTER_VALIDATE_INT);
if(!$case_id) {
    $case_id = 1; // Default test case ID
    echo "<div class='warning'>WARNING: Using default case ID 1 for testing</div>";
}

// Get case details
try {
// Updated query with proper GROUP BY
$stmt = $db->prepare("
    SELECT k.*, cs.status_name, v.Name AS victim_name, v.Statement,
           GROUP_CONCAT(DISTINCT o.Full_Name) AS officers,
           GROUP_CONCAT(DISTINCT l.Full_Name) AS lawyers,
           c.Court_Name, c.Judge_Name, c.Address AS court_address
    FROM kcases_id k
    JOIN case_statuses cs ON k.status_id = cs.status_id
    JOIN victim v ON k.Cases_ID = v.Cases_ID
    LEFT JOIN assignment_id a ON k.Cases_ID = a.Cases_ID
    LEFT JOIN officer_id oi ON a.Officer_ID = oi.Officer_ID
    LEFT JOIN user_id o ON oi.User_ID = o.User_ID
    LEFT JOIN lawyers_id li ON k.Cases_ID = li.Cases_ID
    LEFT JOIN user_id l ON li.User_ID = l.User_ID
    LEFT JOIN court c ON k.Cases_ID = c.Cases_ID
    WHERE k.Cases_ID = ?
    GROUP BY k.Cases_ID, k.Crime_Type, k.User_ID, k.status_id, k.Created_At,
             v.Victim_ID, v.Name, v.Statement, v.Date_Recorded, v.Recorded_By_User_ID, v.User_ID,
             c.Court_ID, c.Judge_Name, c.Court_Name, c.Address
");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$case) {
        die("<div class='error'>Test case not found. Please ensure test data exists in database.</div>");
    }

    // Get evidence
    $stmt = $db->prepare("SELECT * FROM evidence_id WHERE Cases_ID = ?");
    $stmt->execute([$case_id]);
    $evidence = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get upcoming hearings
    $stmt = $db->prepare("
        SELECT h.*, c.Court_Name 
        FROM hearing_dates h
        JOIN court c ON h.Court_ID = c.Court_ID
        WHERE h.Cases_ID = ? AND h.Hearing_Date > NOW()
        ORDER BY h.Hearing_Date
    ");
    $stmt->execute([$case_id]);
    $hearings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("<div class='error'>Database error: " . $e->getMessage() . "</div>");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Case Details - TESTING VERSION</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .warning {
            background-color: #FFF3CD;
            color: #856404;
            padding: 15px;
            margin: 10px;
            border: 1px solid #FFEEBA;
            border-radius: 4px;
        }
        .error {
            background-color: #F8D7DA;
            color:rgb(101, 59, 91);
            padding: 15px;
            margin: 10px;
            border: 1px solid #F5C6CB;
            border-radius: 4px;
        }
        .header {
            background-color:rgb(88, 57, 80);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .case-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .back-btn {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background-color: #6C757D;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Case Details - #<?= $case_id ?></h1>
        <a href="main.php" class="back-btn">Back to Portal</a>
    </div>
    
    
    <div class="container">
        <div class="case-section">
            <h2><?= htmlspecialchars($case['Crime_Type']) ?></h2>
            <p>Status: <strong><?= htmlspecialchars($case['status_name']) ?></strong></p>
            <p>Opened on: <?= date('M d, Y', strtotime($case['Created_At'])) ?></p>
        </div>
        
        <div class="case-section">
            <h3>Victim Information</h3>
            <p>Name: <?= htmlspecialchars($case['victim_name']) ?></p>
            <p>Statement: <?= nl2br(htmlspecialchars($case['Statement'])) ?></p>
        </div>
        
        <?php if(!empty($case['officers'])): ?>
        <div class="case-section">
            <h3>Assigned Officers</h3>
            <p><?= str_replace(',', ', ', htmlspecialchars($case['officers'])) ?></p>
        </div>
        <?php endif; ?>
        
        <?php if(!empty($hearings)): ?>
        <div class="case-section">
            <h3>Upcoming Hearings</h3>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Court</th>
                    <th>Purpose</th>
                </tr>
                <?php foreach($hearings as $hearing): ?>
                <tr>
                    <td><?= date('M d, Y H:i', strtotime($hearing['Hearing_Date'])) ?></td>
                    <td><?= htmlspecialchars($hearing['Court_Name']) ?></td>
                    <td><?= htmlspecialchars($hearing['Purpose']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>