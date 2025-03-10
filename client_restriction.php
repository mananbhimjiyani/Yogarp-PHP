<?php
require_once 'Path.php';
require_once 'db.php';
include 'includes/header.php';

// Initialize variables
$search_client = '';
$client_data = [];
$client_restrictions = [];
$show_client_info = false;
$batch_name = '';
$studio_name = '';

// Start session and check user type
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 1; // Default to 1 if not set

// Handle search query
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search_client'])) {
        $search_client = $_POST['search_client'];
        $search_client = $conn->real_escape_string($search_client); // Prevent SQL injection

        // Adjust search query based on user type
        if ($user_type == 2) {
            $sql = "SELECT * FROM clients WHERE client_id LIKE '%$search_client%' OR first_name LIKE '%$search_client%' OR last_name LIKE '%$search_client%'";
        } else {
            $sql = "SELECT * FROM clients WHERE client_id LIKE '%$search_client%' OR first_name LIKE '%$search_client%' OR last_name LIKE '%$search_client%' OR mobile_number LIKE '%$search_client%'";
        }

        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $client_data[] = $row;
            }
        }

        if (!empty($client_data)) {
            $client_id = $client_data[0]['client_id']; // Use the first client in the search results
            $show_client_info = true;

            // Fetch client restrictions
            $sql_restrictions = "SELECT cr.*, a.name AS asana_name
                                 FROM client_restriction cr
                                 JOIN asana a ON cr.asana_id = a.id
                                 WHERE cr.client_id = $client_id";
            $result_restrictions = $conn->query($sql_restrictions);
            if ($result_restrictions) {
                while ($row = $result_restrictions->fetch_assoc()) {
                    $client_restrictions[] = $row;
                }
            }

            // Fetch the batch name
            $sql_batch_name = "SELECT b.batch_name
                               FROM studio_batch sb
                               JOIN batch b ON sb.batch_id = b.batch_id
                               WHERE sb.studio_batch_id = (SELECT cba.studio_batch_id FROM assign_batch_to_client cba WHERE cba.client_id = $client_id LIMIT 1)";
            $result_batch_name = $conn->query($sql_batch_name);
            if ($result_batch_name && $result_batch_name->num_rows > 0) {
                $batch_row = $result_batch_name->fetch_assoc();
                $batch_name = $batch_row['batch_name'];
            }

            // Fetch the studio name
            $sql_studio_name = "SELECT s.studio_name
                                FROM studio s
                                WHERE s.studio_id = (SELECT sb.studio_id FROM studio_batch sb JOIN assign_batch_to_client cba ON sb.studio_batch_id = cba.studio_batch_id WHERE cba.client_id = $client_id LIMIT 1)";
            $result_studio_name = $conn->query($sql_studio_name);
            if ($result_studio_name && $result_studio_name->num_rows > 0) {
                $studio_row = $result_studio_name->fetch_assoc();
                $studio_name = $studio_row['studio_name'];
            }
        }
    }
}

// Fetch all asanas for the dropdown
$asana_query = "SELECT * FROM asana";
$asana_result = $conn->query($asana_query);
$asanas = [];
if ($asana_result) {
    while ($row = $asana_result->fetch_assoc()) {
        $asanas[] = $row;
    }
}
?>

<h2 class="mb-4">Client Restrictions</h2>

<!-- Client Search Form -->
<form method="POST" action="" class="mb-4">
    <div class="input-group">
        <input type="text" class="form-control" name="search_client" placeholder="Search by Client ID, First Name, Last Name<?php if ($user_type != 2) { echo ', Mobile Number'; } ?>" value="<?php echo htmlspecialchars($search_client); ?>">
        <button class="btn btn-primary" type="submit" name="search_client">Search</button>
    </div>
</form>

<!-- Display Client Data -->
<?php if ($show_client_info && !empty($client_data)): ?>
    <?php $client = $client_data[0]; // Assuming only one client per search ?>
    <div class="card mb-4">
        <div class="card-header">
            <h3>Client Information</h3>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <img src="<?php echo htmlspecialchars($client['photo'] ?? 'default_photo_path.jpg'); ?>" alt="Photo" class="img-fluid rounded-circle" style="width: 50px;">
                </div>
                <div class="col-md-9">
                    <h4><?php echo htmlspecialchars($client['title'] . ' ' . $client['first_name'] . ' ' . $client['last_name']); ?></h4>
                    <?php if ($user_type == 1): ?>
                        <p><?php echo htmlspecialchars($client['mobile_number']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($studio_name) && !empty($batch_name)): ?>
                        <p><strong>Studio Name:</strong> <?php echo htmlspecialchars($studio_name); ?></br>
                        <strong>Batch Name:</strong> <?php echo htmlspecialchars($batch_name); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Restrictions Table -->
    <div class="card">
        <div class="card-header">
            <h3>Restrictions</h3>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th class="text-start">Asana Name</th>
                        <th class="text-center">Do's</th>
                        <th class="text-center">Don'ts</th>
                        <?php if ($user_type == 1): ?>
                            <th class="text-center">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($client_restrictions as $restriction): ?>
                    <tr>
                        <td class="text-start"><?php echo htmlspecialchars($restriction['asana_name']); ?></td>
                        <td class="text-center align-middle">
                            <?php if ($restriction['action'] == 0): ?>
                                <i class="bi bi-check-circle-fill text-success"></i>
                            <?php else: ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center align-middle">
                            <?php if ($restriction['action'] == 1): ?>
                                <i class="bi bi-slash-circle-fill text-danger"></i>
                            <?php else: ?>
                            <?php endif; ?>
                        </td>
                        <?php if ($user_type == 1): ?>
                        <td class="text-center">
                            <form method="POST" action="update_client_restriction.php" class="d-inline">
                                <input type="hidden" name="restriction_id" value="<?php echo htmlspecialchars($restriction['id']); ?>">
                                <button class="btn btn-warning btn-sm" type="submit">Update</button>
                            </form>
                            <form method="POST" action="delete_client_restriction.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this restriction?');">
                                <input type="hidden" name="restriction_id" value="<?php echo htmlspecialchars($restriction['id']); ?>">
                                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
