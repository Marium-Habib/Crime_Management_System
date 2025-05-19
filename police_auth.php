<?php
require_once 'police_config.php';
require_once 'Database.php';
require_once 'User.php';
require_once 'CaseManager.php';

// Initialize variables
$currentUser = null;
$officer = null;
$userType = null;

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in for protected pages
$current_page = basename($_SERVER['PHP_SELF']);
$login_page = 'police_login.php';

if ($current_page != $login_page && empty($_SESSION['user_id'])) {
    header('Location: ' . $login_page);
    exit();
}

$db = new Database();
$user = new User($db);
$caseManager = new CaseManager($db);

// Get current user details if logged in
if (!empty($_SESSION['user_id'])) {
    $currentUser = $user->findUserByUsername($_SESSION['username']);
    
    if (!$currentUser) {
        session_destroy();
        header('Location: ' . $login_page);
        exit();
    }

    $userType = $user->getUserType($currentUser->User_ID);

    // Verify user is an officer for protected pages
    if ($current_page != $login_page && $userType !== 'officer') {
        session_destroy();
        header('Location: ' . $login_page);
        exit();
    }

    // Get officer details
    if ($userType === 'officer') {
        $officer = $user->getOfficerDetails($currentUser->User_ID);
        
        if (!$officer) {
            // Handle case where user is marked as officer but no officer record exists
            session_destroy();
            header('Location: ' . $login_page . '?error=no_officer_record');
            exit();
        }
    }
}

// Function to check case assignment
function isOfficerAssigned($case_id, $officer_id, $caseManager) {
    if (empty($officer_id)) {
        return false;
    }
    $case = $caseManager->getCaseById($case_id, $officer_id);
    return ($case !== false);
}
?>