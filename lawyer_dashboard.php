<?php
require_once 'db_config.php';
require_once 'auth.php';

// Get lawyer's cases
$lawyer_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT DISTINCT k.*, cs.status_name 
                      FROM kcases_id k 
                      JOIN case_statuses cs ON k.status_id = cs.status_id 
                      JOIN lawyers_id l ON k.User_ID = l.User_ID 
                      WHERE k.User_ID = ?");
$stmt->execute([$lawyer_id]);
$cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'header.php'; ?>

<h2>My Cases</h2>

<?php if (empty($cases)): ?>
    <div class="alert alert-info">No cases assigned to you yet.</div>
<?php else: ?>
    <div class="card">
        <div class="card-header">Case List</div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>Crime Type</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cases as $case): ?>
                    <tr>
                        <td><?= htmlspecialchars($case['Cases_ID']) ?></td>
                        <td><?= htmlspecialchars($case['Crime_Type']) ?></td>
                        <td><?= htmlspecialchars($case['status_name']) ?></td>
                        <td><?= date('M d, Y H:i', strtotime($case['Created_At'])) ?></td>
                        <td>
                            <a href="case_details.php?id=<?= $case['Cases_ID'] ?>" class="btn btn-primary">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php include 'footer.php'; ?>