<?php 
$title = 'View Case';
require_once 'police_auth.php';

if(!isset($_GET['id']) || !isOfficerAssigned($_GET['id'], $officer->Officer_ID, $caseManager)) {
    header('Location: police_dashboard.php');
    exit();
}

$case = $caseManager->getCaseById($_GET['id'], $officer->Officer_ID);
$evidence = $caseManager->getCaseEvidence($case->Cases_ID);
$suspects = $caseManager->getCaseSuspects($case->Cases_ID);
$victims = $caseManager->getCaseVictims($case->Cases_ID);

include 'police_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Case #<?php echo $case->Cases_ID; ?></h1>
    <div>
        <a href="case_edit.php?id=<?php echo $case->Cases_ID; ?>" class="btn btn-warning">Edit</a>
        <a href="police_dashboard.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Case Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3"><strong>Crime Type:</strong></div>
                    <div class="col-md-9"><?php echo $case->Crime_Type; ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><strong>Status:</strong></div>
                    <div class="col-md-9">
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
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><strong>Date Opened:</strong></div>
                    <div class="col-md-9"><?php echo date('F j, Y, g:i a', strtotime($case->Created_At)); ?></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Evidence</h5>
            </div>
            <div class="card-body">
                <?php if(empty($evidence)): ?>
                    <p>No evidence recorded for this case.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                    <th>Date Found</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($evidence as $item): ?>
                                <tr>
                                    <td><?php echo $item->Type; ?></td>
                                    <td><?php echo $item->Status; ?></td>
                                    <td><?php echo $item->Location_Found; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($item->Date_Found)); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Suspects</h5>
            </div>
            <div class="card-body">
                <?php if(empty($suspects)): ?>
                    <p>No suspects recorded for this case.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach($suspects as $suspect): ?>
                        <li class="list-group-item"><?php echo $suspect->Name; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Victims</h5>
            </div>
            <div class="card-body">
                <?php if(empty($victims)): ?>
                    <p>No victims recorded for this case.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach($victims as $victim): ?>
                        <li class="list-group-item"><?php echo $victim->Name; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'police_footer.php'; ?>