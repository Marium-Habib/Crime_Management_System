<?php
require_once 'police_config.php';
require_once 'Database.php';
require_once 'User.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: police_dashboard.php');
    exit();
}

$db = new Database();
$user = new User($db);

$username = $password = '';
$username_err = $password_err = $login_err = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate username
    if (empty(trim($_POST['username']))) {
        $username_err = 'Please enter username.';
    } else {
        $username = trim($_POST['username']);
    }
    
    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter your password.';
    } else {
        $password = trim($_POST['password']);
    }
    
    if (empty($username_err) && empty($password_err)) {
        $loggedInUser = $user->login($username, $password);
        
        if ($loggedInUser) {
            $userType = $user->getUserType($loggedInUser->User_ID);
            
            if ($userType === 'officer') {
                // Store data in session variables
                $_SESSION['user_id'] = $loggedInUser->User_ID;
                $_SESSION['username'] = $loggedInUser->Username;
                $_SESSION['full_name'] = $loggedInUser->Full_Name;
                
                // Get officer details
                $officer = $user->getOfficerDetails($loggedInUser->User_ID);
                if ($officer) {
                    $_SESSION['officer_rank'] = $officer->Rank;
                }
                
                header('Location: police_dashboard.php');
                exit();
            } else {
                $login_err = 'Only police officers can access this portal.';
            }
        } else {
            $login_err = 'Invalid username or password.';
        }
    }
}

$title = 'Login';
include 'police_header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Police Officer Login</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($login_err)): ?>
                    <div class="alert alert-danger"><?php echo $login_err; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <div class="invalid-feedback"><?php echo $username_err; ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <div class="invalid-feedback"><?php echo $password_err; ?></div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'police_footer.php'; ?>