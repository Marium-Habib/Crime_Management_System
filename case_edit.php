<?php 
$title = 'Edit Case';
require_once 'police_auth.php';

$isEdit = isset($_GET['id']);
$statuses = $caseManager->getCaseStatuses();

if($isEdit) {
    if(!isOfficerAssigned($_GET['id'], $officer->Officer_ID, $caseManager)) {
        header('Location: police_dashboard.php');
        exit();
    }
    $case = $caseManager->getCaseById($_GET['id'], $officer->Officer_ID);
}

$crime_type = $isEdit ? $case->Crime_Type : '';
$status_id = $isEdit ? $case->status_id : '';
$crime_type_err = $status_id_err = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(empty(trim($_POST['crime_type']))) {
        $crime_type_err = 'Please enter crime type.';
    } else {
        $crime_type = trim($_POST['crime_type']);
    }
    
    if(empty(trim($_POST['status_id']))) {
        $status_id_err = 'Please select status.';
    } else {
        $status_id = trim($_POST['status_id']);
    }
    
    if(empty($crime_type_err) && empty($status_id_err)) {
        if($isEdit) {
            $data = [
                'crime_type' => $crime_type,
                'status_id' => $status_id,
                'case_id' => $case->Cases_ID
            ];
            
            if($caseManager->updateCase($data)) {
                $_SESSION['success'] = 'Case updated successfully.';
                header('Location: case_view.php?id=' . $case->Cases_ID);
                exit();
            } else {
                $_SESSION['error'] = 'Failed to update case.';
            }
        } else {
            $data = [
                'crime_type' => $crime_type,
                'user_id' => $_SESSION['user_id'],
                'officer_id' => $officer->Officer_ID
            ];
            
            if($caseManager->addCase($data)) {
                $_SESSION['success'] = 'Case added successfully.';
                header('Location: police_dashboard.php');
                exit();
            } else {
                $_SESSION['error'] = 'Failed to add case.';
            }
        }
    }
}

include 'police_header.php';
?>

<div class="card">
    <div class="card-header">
        <h4><?php echo $isEdit ? 'Edit' : 'Add'; ?> Case</h4>
    </div>
    <div class="card-body">
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . ($isEdit ? '?id=' . $case->Cases_ID : ''); ?>" method="post">
            <div class="mb-3">
                <label class="form-label">Crime Type</label>
                <input type="text" name="crime_type" class="form-control <?php echo (!empty($crime_type_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $crime_type; ?>">
                <div class="invalid-feedback"><?php echo $crime_type_err; ?></div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status_id" class="form-control <?php echo (!empty($status_id_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">Select Status</option>
                    <?php foreach($statuses as $status): ?>
                        <option value="<?php echo $status->status_id; ?>" <?php echo ($status_id == $status->status_id) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($status->status_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?php echo $status_id_err; ?></div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?php echo $isEdit ? 'case_view.php?id=' . $case->Cases_ID : 'police_dashboard.php'; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php include 'police_footer.php'; ?>