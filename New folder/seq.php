<?php
require_once 'db.php'; // Database connection

// Fetch asanas for dropdown selection
$asanaResult = $conn->query("SELECT asana_id, asana_name, asana_type, asana_subtype, asana_length FROM asana");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asana Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <form method="POST" id="asana-form">
        <div class="row">
            <div class="col-md-3">
                <label for="asana_id" class="form-label">Asana Name</label>
                <select class="form-select" id="asana_id" name="asana_id" required onchange="fillAsanaDetails()">
                    <option value="">Select Asana</option>
                    <?php while ($asana = $asanaResult->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($asana['asana_id']) ?>"
                                data-asana-name="<?= htmlspecialchars($asana['asana_name']) ?>"
                                data-type="<?= htmlspecialchars($asana['asana_type']) ?>"
                                data-subtype="<?= htmlspecialchars($asana['asana_subtype']) ?>"
                                data-asana-length="<?= htmlspecialchars($asana['asana_length']) ?>">
                            <?= htmlspecialchars($asana['asana_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="type" class="form-label">Type</label>
                <input type="text" class="form-control" id="type" name="type" readonly>
            </div>
            <div class="col-md-3">
                <label for="subtype" class="form-label">Subtype</label>
                <input type="text" class="form-control" id="subtype" name="subtype" readonly>
            </div>
            <div class="col-md-3">
                <label for="time" class="form-label">Time (HH:MM:SS)</label>
                <input type="text" class="form-control" id="time" name="time" readonly>
            </div>
        </div>
    </form>
</div>

<script>
    function fillAsanaDetails() {
        const selectedOption = document.getElementById('asana_id').selectedOptions[0];
        document.getElementById('type').value = selectedOption.getAttribute('data-type');
        document.getElementById('subtype').value = selectedOption.getAttribute('data-subtype');
        
        const asanaLength = selectedOption.getAttribute('data-asana-length');
        const timeField = document.getElementById('time');

        if (asanaLength && asanaLength !== "00:00:00") {
            // Set the time field directly with asana_length in HH:MM:SS format
            timeField.value = asanaLength;
            timeField.readOnly = true;
        } else {
            timeField.value = '';
            timeField.readOnly = false;
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
