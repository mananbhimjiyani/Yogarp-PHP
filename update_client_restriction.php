<?php
// Start output buffering
ob_start();

require_once 'Path.php';
require_once 'db.php';
include 'includes/header.php';

// Initialize variables
$restriction_id = '';
$asana_id = '';
$action = '';
$note = '';
$asanas = [];
$show_form = false;
$show_confirmation = false;
$client_id = ''; // Variable to store client ID

// Fetch restriction data if ID is provided
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['restriction_id'])) {
    $restriction_id = $_POST['restriction_id'];
    $restriction_id = $conn->real_escape_string($restriction_id);

    // Fetch existing restriction data
    $sql = "SELECT * FROM client_restriction WHERE id = $restriction_id";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $restriction = $result->fetch_assoc();
        $asana_id = $restriction['asana_id'];
        $action = $restriction['action'];
        $note = $restriction['note'];
        $client_id = $restriction['client_id']; // Store client ID
    } else {
        echo '<div class="alert alert-danger">Restriction not found.</div>';
        include 'includes/footer.php';
        ob_end_flush(); // Flush the buffer and send headers
        exit;
    }

    // Fetch all asanas for the dropdown
    $asana_query = "SELECT * FROM asana";
    $asana_result = $conn->query($asana_query);
    if ($asana_result) {
        while ($row = $asana_result->fetch_assoc()) {
            $asanas[] = $row;
        }
    }

    // Check if confirmation form is submitted
    if (isset($_POST['confirm_update'])) {
        $asana_id = $_POST['asana_id'];
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $note = $_POST['note'];

        $asana_id = $conn->real_escape_string($asana_id);
        $action = $conn->real_escape_string($action);
        $note = $conn->real_escape_string($note);

        // Update the restriction in the database
        $update_sql = "UPDATE client_restriction SET asana_id = '$asana_id', action = '$action', note = '$note' WHERE id = $restriction_id";
        if ($conn->query($update_sql) === TRUE) {
            // Redirect to client_restriction.php with the client_id
            header("Location: client_restriction.php?client_id=" . urlencode($client_id)); // Use the client_id from the query
            ob_end_flush(); // Ensure all output is flushed before redirect
            exit;
        } else {
            echo '<div class="alert alert-danger">Error updating restriction: ' . $conn->error . '</div>';
        }
    }

    $show_form = true;
} else {
    echo '<div class="alert alert-danger">Invalid request.</div>';
    include 'includes/footer.php';
    ob_end_flush();
    exit;
}

if ($show_form):
?>

<h2 class="mb-4">Update Client Restriction</h2>

<!-- Update Restriction Form -->
<form method="POST" action="">
    <input type="hidden" name="restriction_id" value="<?php echo htmlspecialchars($restriction_id); ?>">

    <div class="mb-3">
        <label for="asana_id" class="form-label">Asana</label>
        <select id="asana_id" name="asana_id" class="form-select" required>
            <option value="" disabled>Select Asana</option>
            <?php foreach ($asanas as $asana): ?>
                <option value="<?php echo htmlspecialchars($asana['id']); ?>" <?php echo $asana_id == $asana['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($asana['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Action</label>
        <div class="form-check">
            <input class="form-check-input" type="radio" id="action_do" name="action" value="0" <?php echo $action == 0 ? 'checked' : ''; ?> required>
            <label class="form-check-label" for="action_do">
                Do's
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" id="action_dont" name="action" value="1" <?php echo $action == 1 ? 'checked' : ''; ?> required>
            <label class="form-check-label" for="action_dont">
                Don'ts
            </label>
        </div>
    </div>

    <div class="mb-3">
        <label for="note" class="form-label">Note</label>
        <textarea id="note" name="note" class="form-control" rows="3"><?php echo htmlspecialchars($note ?? ''); ?></textarea>
    </div>

    <!-- Confirmation Form -->
    <div class="d-grid gap-2">
        <button type="submit" name="confirm_update" class="btn btn-primary">Confirm Update</button>
    </div>
</form>

<?php
endif;
include 'includes/footer.php';
ob_end_flush(); // Ensure all output is flushed
?>
