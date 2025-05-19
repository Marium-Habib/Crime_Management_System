<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FIR Crime Report</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>FIR Crime Report</h1>
        <p>Please fill all required details to register your complaint</p>
    </header>

    <div class="form-container">
        <?php if (isset($_GET['success'])): ?>
    <div id="successMessage">
        <p>✅ Your FIR has been registered successfully.</p>
        <!-- Add this button/link -->
        <div style="margin-top: 20px;">
            <button onclick="window.location.href='main.html'" class="back-button">Back to Main Page</button>
        </div>
    </div>
<?php else: ?>
            <form id="crimeForm" action="submit_report.php" method="POST">
                <!-- Personal Information -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Full Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label class="required">Father/Husband Name</label>
                        <input type="text" name="father_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="required">CNIC</label>
                        <input type="text" name="cnic" placeholder="XXXXX-XXXXXXX-X" required>
                    </div>
                    <div class="form-group">
                        <label class="required">Phone</label>
                        <input type="tel" name="phone" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">Complete Address</label>
                    <textarea name="address" required></textarea>
                </div>

                <!-- Incident Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Incident Date & Time</label>
                        <input type="datetime-local" name="incident_datetime" required>
                    </div>
                    <div class="form-group">
                        <label class="required">Crime Type</label>
                        <select name="crime_type" id="crime_type" required onchange="toggleOtherCrime()">
                            <option value="">Select Crime Type</option>
                            <option value="theft">Theft/Burglary</option>
                            <option value="robbery">Robbery/Dacoity</option>
                            <option value="assault">Physical Assault</option>
                            <option value="sexual_harassment">Sexual Harassment</option>
                            <option value="fraud">Financial Fraud</option>
                            <option value="cyber_crime">Cyber Crime</option>
                            <option value="property_dispute">Property Dispute</option>
                            <option value="threat">Criminal Intimidation/Threat</option>
                            <option value="other">Other (Please Specify)</option>
                        </select>
                        <input type="text" name="other_crime_type" id="other_crime_type" placeholder="Please specify crime type">
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">Incident Location</label>
                    <input type="text" name="location" required>
                </div>

                <div class="form-group">
                    <label class="required">Detailed Description</label>
                    <textarea name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label>Suspect Details (if known)</label>
                    <textarea name="suspect_details"></textarea>
                </div>

                <div class="form-group">
                    <label><input type="checkbox" name="declaration" required> 
                    I declare that all information provided is true and correct</label>
                </div>

                <button type="submit">Register FIR</button>
            </form>
            
            <script>
                function toggleOtherCrime() {
                    const crimeType = document.getElementById('crime_type');
                    const otherCrimeField = document.getElementById('other_crime_type');
                    otherCrimeField.style.display = (crimeType.value === 'other') ? 'block' : 'none';
                    if (crimeType.value !== 'other') {
                        otherCrimeField.value = '';
                    }
                }
            </script>
        <?php endif; ?>
    </div>
</body>
</html>