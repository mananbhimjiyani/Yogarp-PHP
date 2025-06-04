<?php
require_once 'db.php'; // Database connection
require_once 'includes/batch_functions.php'; // Include the function to get batches and studios

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $batch_id = $_POST['batch_id'];
    $studio_id = $_POST['studio_id'];

    // Insert query to save batch_id and studio_id
    $sql = "INSERT INTO studio_batch (batch_id, studio_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $batch_id, $studio_id);

    if ($stmt->execute()) {
        $success_message = "Batch and studio successfully inserted.";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all batches and associated studios
$batches_with_studios = getAllBatchesAndStudios();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Data</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Membership Data</h2>

        <!-- Success/Error Message Display -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?= $success_message; ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <!-- Batch Selection -->
            <div class="col-md-6">
                <label for="batch_id" class="form-label">Batch</label>
                <select name="batch_id" id="batch_id" class="form-select" onchange="fetchStudios(this.value)" required>
                    <option value="">Select a Batch</option>
                    <?php foreach ($batches_with_studios as $batch): ?>
                        <option value="<?= $batch['batch_id']; ?>"><?= $batch['batch_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Studio Selection -->
            <div class="col-md-6">
                <label for="studio_id" class="form-label">Studio</label>
                <select name="studio_id" id="studio_id" class="form-select" required>
                    <option value="">Select a Studio</option>
                    <!-- Studios will be dynamically populated based on the selected batch -->
                </select>
            </div>

            <!-- Additional Fields (Start Date, End Date, Remarks, etc.) -->
            <div class="col-md-6">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control">
            </div>
            <div class="col-md-12">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control"></textarea>
            </div>
            <div class="col-md-3">
                <label for="active" class="form-label">Active</label>
                <input type="checkbox" name="active" value="1" class="form-check-input">
            </div>

            <!-- Submit Button -->
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Insert</button>
            </div>
        </form>
    </div>

    <!-- Prepare JavaScript Batch-Studio Mapping -->
    <script>
    const batchStudioMap = {};
    
    // Generate the JavaScript map for batch and studios dynamically from PHP
    <?php foreach ($batches_with_studios as $batch): ?>
        batchStudioMap[<?= $batch['batch_id']; ?>] = <?= json_encode($batch['studios']); ?>;
    <?php endforeach; ?>

    // Debug: Log batchStudioMap to check structure
    console.log(batchStudioMap);
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // JavaScript to dynamically fetch studios based on selected batch
    function fetchStudios(batchId) {
        if (batchId) {
            // Clear the previous studio options
            const studioSelect = document.getElementById('studio_id');
            studioSelect.innerHTML = '<option value="">Select a Studio</option>';
            
            // Fetch the associated studios using a hidden array in JavaScript (coming from PHP)
            const batchStudios = batchStudioMap[batchId];

            // Debug: Log batchStudios to check the values for the selected batch
            console.log(batchStudios);

            // Add new studio options
            batchStudios.forEach(studio => {
                const option = document.createElement('option');
                option.value = studio.studio_id; // Use studio_id as the value
                option.text = studio.studio_name; // Display studio_name
                studioSelect.appendChild(option);
            });
        }
    }
    </script>
	
<script>
document.addEventListener('DOMContentLoaded', function () {
    const demoDateInput = document.getElementById('demo_date');
    const batchSelect = document.getElementById('batch_id');
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    demoDateInput.setAttribute('min', today);

    // Function to check if a date is a 2nd or 4th Saturday
    function is2ndOr4thSaturday(date) {
        const dayOfMonth = date.getDate();
        const dayOfWeek = date.getDay();
        
        if (dayOfWeek !== 6) return false;  // Not Saturday
        const nthWeek = Math.ceil(dayOfMonth / 7);
        return nthWeek === 2 || nthWeek === 4;  // 2nd or 4th Saturday
    }

    // Disable Sundays and restrict 2nd and 4th Saturday evening batch
    demoDateInput.addEventListener('change', function () {
        const selectedDate = new Date(demoDateInput.value);
        const dayOfWeek = selectedDate.getDay();

        if (dayOfWeek === 0) {  // Sunday
            alert('All Sundays and every 2nd and 4th Saturday we have Week off');
            demoDateInput.value = '';
        } else if (is2ndOr4thSaturday(selectedDate)) {
            // Check if evening batch is selected
            const selectedBatch = batchSelect.options[batchSelect.selectedIndex].text.toLowerCase();
            if (selectedBatch.includes('evening')) {
                alert('All Sundays and every 2nd and 4th Saturday evening we have Week off');
                batchSelect.value = '';
            }
        }
    });

    // Optionally handle batch selection logic as well
    batchSelect.addEventListener('change', function () {
        const selectedDate = new Date(demoDateInput.value);
        if (is2ndOr4thSaturday(selectedDate)) {
            const selectedBatch = batchSelect.options[batchSelect.selectedIndex].text.toLowerCase();
            if (selectedBatch.includes('evening')) {
                alert('All Sundays and every 2nd and 4th Saturday evening we have Week off');
                batchSelect.value = '';
            }
        }
    });
});
</script>
<?php include 'includes/footer.php'; // Include the footer file ?>
</body>
</html>
