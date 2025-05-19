<?php
// Debugging ke liye (errors dikhao)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "root", "", "crime_management_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Data collect karo from the form fields
$name = $_POST['name'];
$father_name = $_POST['father_name'];
$email = $_POST['email'] ?? null; // Optional field
$cnic = $_POST['cnic'];
$phone = $_POST['phone'];
$address = $_POST['address'];
$incident_datetime = $_POST['incident_datetime'];
$crime_type = $_POST['crime_type'];
// If "other" crime type was selected, use the specified value
if ($crime_type === 'other' && isset($_POST['other_crime_type'])) {
    $crime_type = $_POST['other_crime_type'];
}
$location = $_POST['location'];
$description = $_POST['description'];
$suspect_details = $_POST['suspect_details'] ?? null;
$declaration = isset($_POST['declaration']) ? 1 : 0;

// SQL query (prepared statement for security)
$sql = "INSERT INTO reports (
            name, 
            father_name, 
            email,
            cnic, 
            phone, 
            address,
            incident_datetime,
            crime_type,
            location,
            description,
            suspect_details,
            declaration,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssssssssi",
    $name,
    $father_name,
    $email,
    $cnic,
    $phone,
    $address,
    $incident_datetime,
    $crime_type,
    $location,
    $description,
    $suspect_details,
    $declaration
);

if ($stmt->execute()) {
    // Success redirect
 header("Location: report_crime.php?success=1");
    exit(); // Always call exit after header redirect

} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>