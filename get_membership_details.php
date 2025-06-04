<?php
require_once 'db.php'; // Include database connection

// Check if client_id is provided
if (isset($_GET['client_id'])) {
    $client_id = intval($_GET['client_id']); // Sanitize input

    // Fetch membership details for the given client_id
    $query = "
        SELECT 
            m.client_id, 
            m.plan_id, 
            m.start_date, 
            m.end_date, 
            m.amount, 
            m.couple_name, 
            m.payment_type, 
            m.payment_proff, 
            m.remarks,
            c.first_name,
            c.last_name,
            p.plan_type
        FROM membership m
        INNER JOIN clients c ON m.client_id = c.client_id
        INNER JOIN membership_plan p ON m.plan_id = p.plan_id
        WHERE m.client_id = ? AND m.active = 1
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($membership = $result->fetch_assoc()) {
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