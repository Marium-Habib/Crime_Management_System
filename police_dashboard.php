<?php 
$title = 'Dashboard';
require_once 'police_auth.php';

// Verify officer is properly set
if (!isset($officer) || !is_object($officer) || !property_exists($officer, 'Officer_ID')) {
    die("Access denied or officer data not available. Please contact administrator.");
}

$cases = $caseManager->getOfficerCases($officer->Officer_ID);
include 'police_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>My Cases</h1>
    <a href="case_edit.php" class="btn btn-primary">Add New Case</a>
</div>

<?php if(empty($cases)): ?>
    <div class="alert alert-info">No cases assigned to you yet.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Case ID</th>
                    <th>Crime Type</th>
                    <th>Status</th>
                    <th>Date Opened</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($cases as $case): ?>
                <tr>
                    <td><?php echo $case->Cases_ID; ?></td>
                    <td><?php echo $case->Crime_Type; ?></td>
                    <td>
                        <span class="badge 
                            <?php 
                            switch($case->status_name) {
                                case 'open': echo 'bg-primary'; break;
                                case 'investigating': echo 'bg-warning text-dark'; break;
                                case 'closed': echo 'bg-success'; break;
                                case 'reopened': echo 'bg-danger'; break;
                                default: echo 'bg-secondary';
                            }
                            ?>">
                            <?php echo ucfirst($case->status_name); ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($case->Created_At)); ?></td>
                    <td>
                        <a href="case_view.php?id=<?php echo $case->Cases_ID; ?>" class="btn btn-sm btn-info">View</a>
                        <a href="case_edit.php?id=<?php echo $case->Cases_ID; ?>" class="btn btn-sm btn-warning">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include 'police_footer.php'; ?>