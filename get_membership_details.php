<?php
require_once 'db.php';

if (isset($_GET['client_id'])) {
    $client_id = intval($_GET['client_id']);

    // Get the latest membership details and client information
    $query = "SELECT 
        c.client_id,
        CONCAT(c.title, ' ', c.first_name, ' ', COALESCE(c.middle_name, ''), ' ', c.last_name) as client_name,
        m.plan_id,
        m.end_date as last_end_date,
        mp.plan_type,
        mp.plan_duration,
        mp.amount
        FROM clients c
        LEFT JOIN (
            SELECT client_id, plan_id, end_date
            FROM membership
            WHERE client_id = ? AND active = 1
            ORDER BY end_date DESC
            LIMIT 1
        ) m ON c.client_id = m.client_id
        LEFT JOIN membership_plan mp ON m.plan_id = mp.plan_id
        WHERE c.client_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $client_id, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Convert dates to proper format and calculate next start date
        $lastEndDate = $row['last_end_date'];
        $nextStartDate = null;

        if ($lastEndDate) {
            // Calculate next start date (previous end date + 1 day)
            $nextStartDate = date('Y-m-d', strtotime($lastEndDate . ' +1 day'));
        } else {
            // If no previous membership, use current date
            $nextStartDate = date('Y-m-d');
        }
?>
        <div class="popup-overlay">
            <div class="popup-content">
                <h4>Membership Details</h4>
                <div class="form-group">
                    <label>Client Name:</label>
                    <input type="text" value="<?= htmlspecialchars($membership['first_name'] . ' ' . $membership['last_name']) ?>" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Plan:</label>
                    <input type="text" value="<?= htmlspecialchars($membership['plan_type']) ?>" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Start Date:</label>
                    <input type="text" value="<?= htmlspecialchars($membership['start_date']) ?>" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>End Date:</label>
                    <input type="text" value="<?= htmlspecialchars($membership['end_date']) ?>" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Amount:</label>
                    <input type="text" value="<?= htmlspecialchars($membership['amount']) ?>" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Payment Type:</label>
                    <input type="text" value="<?= htmlspecialchars($membership['payment_type']) ?>" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Payment Proof:</label>
                    <input type="text" value="<?= htmlspecialchars($membership['payment_proff']) ?>" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Remarks:</label>
                    <textarea class="form-control" readonly><?= htmlspecialchars($membership['remarks']) ?></textarea>
                </div>
                <button class="btn btn-secondary" onclick="closePopup()">Close</button>
            </div>
        </div>

        <script>
            function closePopup() {
                $('.popup-overlay').remove();
            }
        </script>
<?php
    } else {
        echo "<div class='alert alert-danger'>No active membership found for the given client.</div>";
    }
} else {
    echo "<div class='alert alert-danger'>Invalid request. Client ID is missing.</div>";
}
?>