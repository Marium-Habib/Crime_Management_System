<?php
require_once 'db_config.php';
require_once 'auth.php';

if (!isset($_GET['id'])) {
    header("Location: lawyer_dashboard.php");
    exit();
}

$case_id = $_GET['id'];
$lawyer_id = $_SESSION['user_id'];

// Verify the lawyer is assigned to this case
$stmt = $pdo->prepare("SELECT COUNT(*) FROM lawyers_id WHERE User_ID = ? AND Cases_ID = ?");
$stmt->execute([$lawyer_id, $case_id]);
if ($stmt->fetchColumn() == 0) {
    header("Location: unauthorized.php");
    exit();
}

// Get case details
$stmt = $pdo->prepare("SELECT k.*, cs.status_name 
                      FROM kcases_id k 
                      JOIN case_statuses cs ON k.status_id = cs.status_id 
                      WHERE k.Cases_ID = ?");
$stmt->execute([$case_id]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);

// Get case documents
$documents = $pdo->prepare("SELECT * FROM case_document WHERE Cases_ID = ?");
$documents->execute([$case_id]);
$documents = $documents->fetchAll(PDO::FETCH_ASSOC);

// Get suspects
$suspects = $pdo->prepare("SELECT * FROM xsuspect_id WHERE Cases_ID = ?");
$suspects->execute([$case_id]);
$suspects = $suspects->fetchAll(PDO::FETCH_ASSOC);

// Get victims
$victims = $pdo->prepare("SELECT * FROM victim WHERE Cases_ID = ?");
$victims->execute([$case_id]);
$victims = $victims->fetchAll(PDO::FETCH_ASSOC);

// Get witnesses
$witnesses = $pdo->prepare("SELECT * FROM witness WHERE Cases_ID = ?");
$witnesses->execute([$case_id]);
$witnesses = $witnesses->fetchAll(PDO::FETCH_ASSOC);

// Get evidence
$evidence = $pdo->prepare("SELECT * FROM evidence_id WHERE Cases_ID = ?");
$evidence->execute([$case_id]);
$evidence = $evidence->fetchAll(PDO::FETCH_ASSOC);

// Get crime location
$location = $pdo->prepare("SELECT * FROM crime_location WHERE Cases_ID = ?");
$location->execute([$case_id]);
$location = $location->fetch(PDO::FETCH_ASSOC);

// Get court details
$court = $pdo->prepare("SELECT * FROM court WHERE Cases_ID = ?");
$court->execute([$case_id]);
$court = $court->fetch(PDO::FETCH_ASSOC);

// Get hearing dates
$hearings = $pdo->prepare("SELECT * FROM hearing_dates WHERE Cases_ID = ? ORDER BY Hearing_Date");
$hearings->execute([$case_id]);
$hearings = $hearings->fetchAll(PDO::FETCH_ASSOC);

// Get judgement
$judgement = $pdo->prepare("SELECT * FROM judgement WHERE Cases_ID = ?");
$judgement->execute([$case_id]);
$judgement = $judgement->fetch(PDO::FETCH_ASSOC);

// Get imprisonment records
$imprisonments = $pdo->prepare("SELECT i.*, s.Name as Suspect_Name 
                               FROM imprisonment i 
                               JOIN xsuspect_id s ON i.Suspect_ID = s.Suspect_ID 
                               WHERE i.Cases_ID = ?");
$imprisonments->execute([$case_id]);
$imprisonments = $imprisonments->fetchAll(PDO::FETCH_ASSOC);

// Get weapons
$weapons = $pdo->prepare("SELECT * FROM weapon WHERE Cases_ID = ?");
$weapons->execute([$case_id]);
$weapons = $weapons->fetchAll(PDO::FETCH_ASSOC);

// Get vehicles
$vehicles = $pdo->prepare("SELECT * FROM vehicle WHERE Cases_ID = ?");
$vehicles->execute([$case_id]);
$vehicles = $vehicles->fetchAll(PDO::FETCH_ASSOC);

// Get forensic reports
$forensics = $pdo->prepare("SELECT * FROM forensic_report WHERE Cases_ID = ?");
$forensics->execute([$case_id]);
$forensics = $forensics->fetchAll(PDO::FETCH_ASSOC);

// Get CCTV evidence
$cctv = $pdo->prepare("SELECT * FROM cctv_evidence WHERE Cases_ID = ?");
$cctv->execute([$case_id]);
$cctv = $cctv->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'header.php'; ?>

<h2>Case Details: <?= htmlspecialchars($case['Crime_Type']) ?> (ID: <?= htmlspecialchars($case['Cases_ID']) ?>)</h2>

<div class="card">
    <div class="card-header">Basic Information</div>
    <div class="card-body">
        <p><strong>Status:</strong> <?= htmlspecialchars($case['status_name']) ?></p>
        <p><strong>Created At:</strong> <?= date('M d, Y H:i', strtotime($case['Created_At'])) ?></p>
    </div>
</div>

<!-- Crime Location -->
<div class="card">
    <div class="card-header">Crime Location</div>
    <div class="card-body">
        <?php if ($location): ?>
            <p><strong>Address:</strong> <?= htmlspecialchars($location['Address']) ?></p>
            <?php if ($location['Coordinates']): ?>
                <p><strong>Coordinates:</strong> <?= htmlspecialchars($location['Coordinates']) ?></p>
            <?php endif; ?>
            <?php if ($location['Description']): ?>
                <p><strong>Description:</strong> <?= htmlspecialchars($location['Description']) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p>No location information available.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Suspects -->
<div class="card">
    <div class="card-header">Suspects</div>
    <div class="card-body">
        <?php if (!empty($suspects)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Criminal History</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suspects as $suspect): ?>
                    <tr>
                        <td><?= htmlspecialchars($suspect['Name']) ?></td>
                        <td><?= htmlspecialchars($suspect['Criminal_History'] ?? 'None') ?></td>
                        <td>
                            <a href="manage_suspect.php?case_id=<?= $case_id ?>&id=<?= $suspect['Suspect_ID'] ?>" class="btn btn-primary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No suspects recorded.</p>
        <?php endif; ?>
        <a href="manage_suspect.php?case_id=<?= $case_id ?>" class="btn btn-success">Add Suspect</a>
    </div>
</div>

<!-- Victims -->
<div class="card">
    <div class="card-header">Victims</div>
    <div class="card-body">
        <?php if (!empty($victims)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($victims as $victim): ?>
                    <tr>
                        <td><?= htmlspecialchars($victim['Name']) ?></td>
                        <td><?= htmlspecialchars($victim['Contact'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($victim['Address'] ?? 'N/A') ?></td>
                        <td>
                            <a href="manage_victim.php?case_id=<?= $case_id ?>&id=<?= $victim['Victim_ID'] ?>" class="btn btn-primary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No victims recorded.</p>
        <?php endif; ?>
        <a href="manage_victim.php?case_id=<?= $case_id ?>" class="btn btn-success">Add Victim</a>
    </div>
</div>

<!-- Witnesses -->
<div class="card">
    <div class="card-header">Witnesses</div>
    <div class="card-body">
        <?php if (!empty($witnesses)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Statement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($witnesses as $witness): ?>
                    <tr>
                        <td><?= htmlspecialchars($witness['Name']) ?></td>
                        <td><?= htmlspecialchars($witness['Contact'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars(substr($witness['Statement'], 0, 50)) ?>...</td>
                        <td>
                            <a href="manage_witness.php?case_id=<?= $case_id ?>&id=<?= $witness['Witness_ID'] ?>" class="btn btn-primary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No witnesses recorded.</p>
        <?php endif; ?>
        <a href="manage_witness.php?case_id=<?= $case_id ?>" class="btn btn-success">Add Witness</a>
    </div>
</div>

<!-- Evidence -->
<div class="card">
    <div class="card-header">Evidence</div>
    <div class="card-body">
        <?php if (!empty($evidence)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evidence as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['Type']) ?></td>
                        <td><?= htmlspecialchars($item['Status']) ?></td>
                        <td><?= htmlspecialchars(substr($item['Description'], 0, 50)) ?>...</td>
                        <td>
                            <a href="manage_evidence.php?case_id=<?= $case_id ?>&id=<?= $item['Evidence_ID'] ?>" class="btn btn-primary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No evidence recorded.</p>
        <?php endif; ?>
        <a href="manage_evidence.php?case_id=<?= $case_id ?>" class="btn btn-success">Add Evidence</a>
    </div>
</div>

<!-- Court and Hearings -->
<!-- Court and Hearings -->
<div class="card">
    <div class="card-header">Court Information</div>
    <div class="card-body">
        <?php if ($court): ?>
            <p><strong>Court Name:</strong> <?= htmlspecialchars($court['Court_Name']) ?></p>
            <p><strong>Judge:</strong> <?= htmlspecialchars($court['Judge_Name']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($court['Address']) ?></p>
            
            <h4>Hearing Dates</h4>
            <?php if (!empty($hearings)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Purpose</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hearings as $hearing): ?>
                        <tr>
                            <td><?= date('M d, Y H:i', strtotime($hearing['Hearing_Date'])) ?></td>
                            <td><?= htmlspecialchars($hearing['Purpose']) ?></td>
                            <td>
                                <a href="manage_court.php?case_id=<?= $case_id ?>&hearing_id=<?= $hearing['Hearing_ID'] ?>" class="btn btn-primary">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hearing dates scheduled.</p>
            <?php endif; ?>
            <a href="manage_court.php?case_id=<?= $case_id ?>" class="btn btn-success">Add Hearing</a>
        <?php else: ?>
            <p>No court information available.</p>
            <a href="manage_court.php?case_id=<?= $case_id ?>" class="btn btn-success">Add Court Info</a>
        <?php endif; ?>
    </div>
</div>

<!-- Judgement -->
<div class="card">
    <div class="card-header">Judgement</div>
    <div class="card-body">
        <?php if ($judgement): ?>
            <p><strong>Verdict:</strong> <?= htmlspecialchars($judgement['Verdict']) ?></p>
            <p><strong>Sentence:</strong> <?= htmlspecialchars($judgement['Sentence']) ?></p>
            <p><strong>Date:</strong> <?= date('M d, Y', strtotime($judgement['Judgement_Date'])) ?></p>
            <div class="d-flex gap-2">
                <a href="manage_judgement.php?case_id=<?= $case_id ?>&id=<?= $judgement['Judgement_ID'] ?>" class="btn btn-primary">Edit</a>
                <a href="manage_judgement.php?case_id=<?= $case_id ?>" class="btn btn-success">Add Another</a>
            </div>
        <?php else: ?>
            <p>No judgement recorded.</p>
            <a href="manage_judgement.php?case_id=<?= $case_id ?>" class="btn btn-success">Add Judgement</a>
        <?php endif; ?>
    </div>
</div>

<!-- Imprisonment -->
<div class="card">
    <div class="card-header">Imprisonment Records</div>
    <div class="card-body">
        <?php if (!empty($imprisonments)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Suspect</th>
                        <th>Prison</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($imprisonments as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['Suspect_Name']) ?></td>
                        <td><?= htmlspecialchars($record['Prison_Name']) ?></td>
                        <td><?= date('M d, Y', strtotime($record['Start_Date'])) ?></td>
                        <td><?= $record['End_Date'] ? date('M d, Y', strtotime($record['End_Date'])) : 'N/A' ?></td>
                        <td><?= htmlspecialchars(substr($record['Details'], 0, 30)) ?>...</td>
                        <td>
                            <a href="manage_imprisonment.php?case_id=<?= $case_id ?>&id=<?= $record['Imprisonment_ID'] ?>" class="btn btn-primary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No imprisonment records.</p>
        <?php endif; ?>
        <a href="manage_imprisonment.php?case_id=<?= $case_id ?>" class="btn btn-success">Add Imprisonment</a>
    </div>
</div>

<!-- Weapons -->
<div class="card">
    <div class="card-header">Weapons</div>
    <div class="card-body">
        <?php if (!empty($weapons)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Serial Number</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weapons as $weapon): ?>
                    <tr>
                        <td><?= htmlspecialchars($weapon['Type']) ?></td>
                        <td><?= htmlspecialchars($weapon['Serial_Number'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($weapon['Status']) ?></td>
                        <td><?= htmlspecialchars(substr($weapon['Description'], 0, 30)) ?>...</td>
                        <td>
                            <a href="manage_weapon.php?case_id=<?= $case_id ?>&id=<?= $weapon['Weapon_ID'] ?>" class="btn btn-primary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No weapons recorded.</p>
        <?php endif; ?>
        <a href="manage_weapons.php?case_id=<?= $case_id ?>" class="btn btn-success">Add Weapon</a>
    </div>
</div>

<!-- Vehicles -->
<div class="card">
    <div class="card-header">Vehicles</div>
    <div class="card-body">
        <?php if (!empty($vehicles)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Plate Number</th>
                        <th>Make/Model</th>
                        <th>Color</th>
                        <th>Owner</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle): ?>
                    <tr>
                        <td><?= htmlspecialchars($vehicle['Plate_Number']) ?></td>
                        <td><?= htmlspecialchars($vehicle['Make']) ?> <?= htmlspecialchars($vehicle['Model']) ?></td>
                        <td><?= htmlspecialchars($vehicle['Color']) ?></td>
                        <td><?= htmlspecialchars($vehicle['Owner_Name'] ?? 'Unknown') ?></td>
                        <td>
                            <a href="manage_vehicle.php?case_id=<?= $case_id ?>&id=<?= $vehicle['Vehicle_ID'] ?>" class="btn btn-primary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No vehicles recorded.</p>
        <?php endif; ?>
        <a href="manage_vehicles.php?case_id=<?= $case_id ?>" class="btn btn-success">Add Vehicle</a>
    </div>
</div>

<!-- Forensic Reports -->
<div class="card">
    <div class="card-header">Forensic Reports</div>
    <div class="card-body">
        <?php if (!empty($forensics)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Lab Name</th>
                        <th>Report Date</th>
                        <th>Findings</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forensics as $report): ?>
                    <tr>
                        <td><?= htmlspecialchars($report['Lab_Name']) ?></td>
                        <td><?= date('M d, Y', strtotime($report['Report_Date'])) ?></td>
                        <td><?= htmlspecialchars(substr($report['Findings'], 0, 50)) ?>...</td>
                        <td>
                            <?php if ($report['File_Path']): ?>
                                <a href="<?= htmlspecialchars($report['File_Path']) ?>" class="btn btn-primary" target="_blank">View</a>
                            <?php endif; ?>
                            <a href="manage_forensic_reports.php?case_id=<?= $case_id ?>&id=<?= $report['Report_ID'] ?>" class="btn btn-secondary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No forensic reports available.</p>
        <?php endif; ?>
        <a href="manage_forensic_reports.php?case_id=<?= $case_id ?>" class="btn btn-success">Add Forensic Report</a>
    </div>
</div>

<!-- CCTV Evidence -->
<div class="card">
    <div class="card-header">CCTV Evidence</div>
    <div class="card-body">
        <?php if (!empty($cctv)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>Timestamp</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cctv as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['Location']) ?></td>
                        <td><?= date('M d, Y H:i', strtotime($c['Timestamp'])) ?></td>
                        <td><?= htmlspecialchars(substr($c['Description'], 0, 50)) ?>...</td>
                        <td>
                            <?php if ($c['File_Path']): ?>
                                <a href="<?= htmlspecialchars($c['File_Path']) ?>" class="btn btn-primary" target="_blank">View</a>
                            <?php endif; ?>
                            <a href="manage_cctv.php?case_id=<?= $case_id ?>&id=<?= $c['CCTV_ID'] ?>" class="btn btn-secondary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No CCTV evidence available.</p>
        <?php endif; ?>
        <a href="manage_cctv.php?case_id=<?= $case_id ?>" class="btn btn-success">Add CCTV Evidence</a>
    </div>
</div>

<!-- Case Documents -->
<div class="card">
    <div class="card-header">Case Documents</div>
    <div class="card-body">
        <?php if (!empty($documents)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>Upload Date</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td><?= htmlspecialchars($doc['Document_Type']) ?></td>
                        <td><?= date('M d, Y', strtotime($doc['Upload_Date'])) ?></td>
                        <td><?= htmlspecialchars(substr($doc['Description'], 0, 50)) ?>...</td>
                        <td>
                            <?php if ($doc['File_Path']): ?>
                                <a href="<?= htmlspecialchars($doc['File_Path']) ?>" class="btn btn-primary" target="_blank">View</a>
                            <?php endif; ?>
                            <a href="manage_documents.php?case_id=<?= $case_id ?>&id=<?= $doc['Document_ID'] ?>" class="btn btn-secondary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No documents uploaded.</p>
        <?php endif; ?>
        <a href="manage_documents.php?case_id=<?= $case_id ?>" class="btn btn-success">Upload Document</a>
    </div>
</div>
<?php include 'footer.php'; ?>