<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crime Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Crime Management System</h1>
        <p>Welcome to the Crime Management Portal</p>
    </header>

    <div class="container">
        <div class="options">
            <div class="option-box" onclick="window.location.href='report_crime.php'">
                <h3>Report a Crime</h3>
                <p>If you want to report a crime, click here.</p>
            </div>
            <div class="option-box" onclick="window.location.href='police_main.php'">
                <h3>Police Portal</h3>
                <p>If you're from the police, click here.</p>
            </div>
            <div class="option-box" onclick="window.location.href='lawyer_main.php'">
                <h3>Lawyer Portal</h3>
                <p>If you're a lawyer, click here.</p>
            </div>
            <!-- Updated User Portal Option -->
            <div class="option-box" onclick="window.location.href='victim_login.php'">
                <h3>Victim Portal</h3>
                <p>Click to track your case.</p>
            </div>
        </div>
    </div>
</body>
</html>