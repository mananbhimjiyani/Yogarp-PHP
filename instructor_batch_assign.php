<?php
require_once 'Path.php';
require_once 'db.php';
require_once 'includes/header.php';
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
// Fetch active applicants (instructors) for the dropdown
$applicants = $conn->query("SELECT id, CONCAT(title, ' ', first_name, ' ', last_name) AS name, mobile_number
                           FROM applicants 
                           WHERE status = 1");

// Handle form submission for data display
$applicant_assignments = [];
$instructor_details = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'fetch_data') {
    $instructor_id = $_POST['instructor_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Fetch instructor details
    $instructor_query = "SELECT * FROM applicants WHERE id = '$instructor_id' AND status = 1";
    $instructor_details = $conn->query($instructor_query)->fetch_assoc();

    // Fetch assignments based on the selected instructor and date range
    $query = "SELECT 
                ss.schedule_id, 
                s.studio_id, 
                s.studio_name, 
                b.batch_name,
                ss.instructor_id, 
                ss.demo_instructor_id, 
                ss.correction_instructor_id, 
                ss.support_instructor_id,
                CONCAT(e.title, ' ', e.first_name, ' ', e.last_name) AS instructor,
                CONCAT(d.title, ' ', d.first_name, ' ', d.last_name) AS demo_instructor,
                CONCAT(c.title, ' ', c.first_name, ' ', c.last_name) AS correction_instructor,
                CONCAT(su.title, ' ', su.first_name, ' ', su.last_name) AS support_instructor,
                ss.Scheduledate
          FROM studio_schedule ss
          LEFT JOIN studio s ON ss.studio_id = s.studio_id
          LEFT JOIN batch b ON ss.batch_id = b.batch_id
          LEFT JOIN applicants e ON ss.instructor_id = e.id
          LEFT JOIN applicants d ON ss.demo_instructor_id = d.id
          LEFT JOIN applicants c ON ss.correction_instructor_id = c.id
          LEFT JOIN applicants su ON ss.support_instructor_id = su.id
          WHERE ss.instructor_id = ? 
          AND ss.Scheduledate BETWEEN ? AND ?
          ORDER BY s.studio_id, ss.Scheduledate DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $instructor_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    // Group assignments by studio
    while ($row = $result->fetch_assoc()) {
        $studio_id = $row['studio_id'];
        $applicant_assignments[$studio_id]['studio_name'] = $row['studio_name'];
        $applicant_assignments[$studio_id]['assignments'][] = $row;
    }
}
?>
<head>
<style>
    /* Photo styles */
    .profile-photo {
        width: 250px; /* Set the width to 150px */
        height: 250px; /* Set the height to 150px */
        object-fit: cover; /* Ensure the image covers the area without distortion */
        border-radius: 50%; /* Maintain the circular shape */
    }

    /* Responsive styles */
    /* Small devices */
    @media (max-width: 25px) {
        .profile-photo {
            width: 25px;
            height: 25px;
        }
    }

    /* Medium devices (tablets, 250px and up) */
    @media (min-width: 40px) {
        .profile-photo {
            width: 40px;
            height: 40px;
        }
    }

    /* Large devices (desktops, 250px and up) */
    @media (min-width: 50px) {
        .profile-photo {
            width: 50px;
            height: 50px;
        }
    }

    /* Extra large devices (250px and up) */
    @media (min-width: 80px) {
        .profile-photo {
            width: 80px;
            height: 80px;
        }
    }
</style>
</head>

<!-- HTML Form for Instructor and Date Range Selection -->
<h2 class="mb-4">Fetch Instructor Assignments</h2>

<form method="POST" action="">
    <div class="row">
        <div class="col-md-6">
            <label for="instructor_id" class="form-label">Instructor</label>
            <select class="form-select" id="instructor_id" name="instructor_id" required>
                <option value="">Select Instructor</option>
                <?php while ($row = $applicants->fetch_assoc()) { ?>
                    <option value="<?= htmlspecialchars($row['id']) ?>" 
                            <?= (isset($instructor_id) && $instructor_id == $row['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['name']) ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" class="form-control" id="start_date" name="start_date" required
                   value="<?= isset($start_date) ? htmlspecialchars($start_date) : '' ?>">
        </div>
        <div class="col-md-2">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" class="form-control" id="end_date" name="end_date" required
                   value="<?= isset($end_date) ? htmlspecialchars($end_date) : '' ?>">
        </div>
        <div class="col-md-2">
            <label for="fetch_data" class="form-label">&nbsp; &nbsp;</label></br>
            <button type="submit" name="action" value="fetch_data" class="btn btn-primary">Fetch Data</button>
        </div>
    </div>
</form>

<!-- Display Instructor Details -->
<?php if ($instructor_details) { ?>
    <div class="card mt-4">
        <h3 class="card-header">Instructor Information</h3>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3 d-flex justify-content-center">
<img src="<?php echo htmlspecialchars($instructor_details['photo'] ?? 'default_photo_path.jpg'); ?>" alt="Photo" class="img-fluid rounded-circle profile-photo">
                </div>
                <div class="col-md-9">
                    <h4><?php echo htmlspecialchars(($instructor_details['title'] ?? '') . ' ' . ($instructor_details['first_name'] ?? '') . ' ' . ($instructor_details['last_name'] ?? '')); ?></h4>
                    <p class="d-flex align-items-center">
                        <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/\D/', '', $instructor_details['mobile_number'] ?? 'N/A')) ?>" target="_blank" class="me-2 d-inline-block">
                            <i class="bi bi-whatsapp text-success"></i>
                        </a>
                        <a href="tel:<?= htmlspecialchars($instructor_details['mobile_number'] ?? '') ?>" class="me-3 d-inline-block">
                            <?= htmlspecialchars($instructor_details['mobile_number'] ?? 'N/A') ?>
                        </a>
                        <span class="ms-2 d-inline-block">
                            <?= htmlspecialchars($instructor_details['email'] ?? 'N/A') ?>
                        </span>
                    </p>
                    <p><strong>Date Range: </strong>
                        <?php
                        $formatted_start_date = DateTime::createFromFormat('Y-m-d', $start_date)->format('d/m/Y');
                        $formatted_end_date = DateTime::createFromFormat('Y-m-d', $end_date)->format('d/m/Y');
                        echo htmlspecialchars($formatted_start_date) . " to " . htmlspecialchars($formatted_end_date);
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<!-- Display Assignments Data -->
<?php if (!empty($applicant_assignments)): ?>
    <?php foreach ($applicant_assignments as $studio_id => $studio_data): ?>
        <h3 class="mt-4"><?= htmlspecialchars($studio_data['studio_name']) ?></h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Schedule Date</th>
                    <th>Batch Name</th>
                    <th>Instructor</th>
                    <th>Demo Instructor</th>
                    <th>Correction Instructor</th>
                    <th>Support Instructor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($studio_data['assignments'] as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['Scheduledate']))) ?></td>
                        <td><?= htmlspecialchars($row['batch_name']) ?></td>

                        <?php
                        // Check if the IDs match the instructor_id
                        $isInstructorMatch = $row['instructor_id'] == $instructor_id;
                        $isDemoInstructorMatch = $row['demo_instructor_id'] == $instructor_id;
                        $isCorrectionInstructorMatch = $row['correction_instructor_id'] == $instructor_id;
                        $isSupportInstructorMatch = $row['support_instructor_id'] == $instructor_id;
                        ?>

                        <td style="text-align: center;">
                            <?= $isInstructorMatch ? '<span style="color: green;">✓</span>' : '<span style="color: red;">X</span>' ?>
                        </td>
                        <td style="text-align: center;">
                            <?= $isDemoInstructorMatch ? '<span style="color: green;">✓</span>' : '<span style="color: red;">X</span>' ?>
                        </td>
                        <td style="text-align: center;">
                            <?= $isCorrectionInstructorMatch ? '<span style="color: green;">✓</span>' : '<span style="color: red;">X</span>' ?>
                        </td>
                        <td style="text-align: center;">
                            <?= $isSupportInstructorMatch ? '<span style="color: green;">✓</span>' : '<span style="color: red;">X</span>' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
<?php else: ?>
    <div class="alert alert-warning mt-4">No records found for the selected instructor and date range.</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
