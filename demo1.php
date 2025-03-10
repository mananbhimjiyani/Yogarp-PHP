<?php
ob_start();
require_once 'Path.php'; // Include your path file
require_once 'db.php'; // Include database connection
include 'includes/header.php'; // Include your header
require_once 'includes/batch_functions.php'; // Include the function to get batches and studios

// SQL to fetch enquiry_ids that are not in the demo table
$sql1 = "SELECT enquiry_id FROM enquiry WHERE enquiry_id NOT IN (SELECT enquiry_id FROM demo)";
$result1 = $conn->query($sql1);

// Store enquiry_ids
$enquiry_ids = [];
if ($result1->num_rows > 0) {
    while ($row = $result1->fetch_assoc()) {
        $enquiry_ids[] = $row['enquiry_id'];
    }
}

// SQL to get details for the selected enquiry_ids
$enquiries = [];
if (!empty($enquiry_ids)) {
    $ids = implode(',', $enquiry_ids);
    $sql2 = "
        SELECT 
           
            enquiry.batch_id AS batch_id, 
            enquiry.first_name, 
            enquiry.middle_name, 
            enquiry.last_name, 
            enquiry.any_pain, 
            enquiry.health_conditions, 
            enquiry.any_medication,
            studio.studio_name, 
            batch.batch_name 
        FROM enquiry WHERE enquiry.enquiry_id IN ($ids)";
    $result2 = $conn->query($sql2);

    while ($row = $result2->fetch_assoc()) {
        // Store full name
        $row['full_name'] = htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
        $enquiries[] = $row;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prepare SQL statement to insert into demo table
    $stmt = $conn->prepare("INSERT INTO demo (enquiry_id, studio_id, batch_id, demo_date, remarks, active, stamp) VALUES (?, ?, ?,  ?, ?, 1, NOW())");
    
    // Bind parameters from the form
    $stmt->bind_param(
        "iiiss",
        $_POST['enquiry_id'],
        $_POST['studio_id'],
        $_POST['batch_id'],
        $_POST['demo_date'],
		$_POST['remarks']
    );

    // Execute the statement
       // Execute the statement
    if ($stmt->execute()) {
        // Redirect to the same page to flush the data and avoid resubmission
        header("Location: demo.php"); 
        exit(); // Always call exit after header redirection
    } else {
        echo "<script>alert('Error adding demo details: " . $stmt->error . "');</script>";
    }
}
// Fetch all batches and associated studios
$batches_with_studios = getAllBatchesAndStudios();
ob_end_flush();
?>

<div class="container">
    <h2>Demo Details</h2>
    <form action="demo.php" method="POST" id="demoForm">
 
       <div class="row" style="padding-bottom:10px">
    <div class="col-md-6">
        <label for="enquiry_select"><strong>Select Enquiry:</strong></label>
        <!-- Input field for search -->
        <input type="text" id="search_enquiry" onkeyup="filterEnquiries()" placeholder="Search for enquiries..." style="margin-bottom: 10px;" />
        <select id="enquiry_select" name="enquiry_id" required onchange="updateSelectedEnquiry(this)">
            <option value="">Select an enquiry</option>
            <?php foreach ($enquiries as $enquiry): ?>
                <option value="<?php echo $enquiry['enquiry_id']; ?>" 
                    data-studio-id="<?php echo htmlspecialchars($enquiry['studio_id']); ?>"
                    data-studio-name="<?php echo htmlspecialchars($enquiry['studio_name'] ?? ''); ?>" 
                    data-batch-name="<?php echo htmlspecialchars($enquiry['batch_name'] ?? ''); ?>"
                    data-any-pain="<?php echo htmlspecialchars($enquiry['any_pain'] ?? ''); ?>"
                    data-any-medication="<?php echo htmlspecialchars($enquiry['any_medication'] ?? ''); ?>"
                    data-health-conditions="<?php echo htmlspecialchars($enquiry['health_conditions'] ?? ''); ?>">
                    <?php echo htmlspecialchars($enquiry['full_name'] ?? ''); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<script>
    function filterEnquiries() {
        // Get the value of the search input
        var input = document.getElementById("search_enquiry").value.toLowerCase();
        var select = document.getElementById("enquiry_select");
        var options = select.options;

        // Loop through all options in the select element
        for (var i = 1; i < options.length; i++) { // Start from 1 to skip the "Select an enquiry" option
            var optionText = options[i].text.toLowerCase();
            // Show or hide the option based on the search input
            options[i].style.display = optionText.includes(input) ? "" : "none";
        }
    }
</script>

		<div class="row" style="padding-bottom:10px; display:none">
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
</div>
        <div class="form-group">
            <label for="any_pain">Any Body Pain:</label>
            <input type="text" class="form-control" id="any_pain" name="any_pain" readonly>
        </div>

        <div class="form-group">
            <label for="any_medicine">Any Medicine:</label>
            <input type="text" class="form-control" id="any_medicine" name="any_medicine" readonly>
        </div>

        <div class="form-group">
            <label for="any_medication">Any Medication:</label>
            <input type="text" class="form-control" id="any_medication" name="any_medication" readonly>
        </div>

        <div class="form-group">
            <label for="demo_date">Demo Date:</label>
            <input type="date" class="form-control" id="demo_date" name="demo_date" required>
        </div>
		        <div class="row" style="padding-bottom:10px">
            <!-- Modal -->

            <div class="col-md-3">
                <label>&nbsp;</label><br>
    <button type="button" class="btn btn-warning" id="Edit">Edit</button>
            </div>
			<div class="col-md-6">&nbsp;</div>
            <div class="col-md-3">
                <label>&nbsp;</label><br>
        <button type="submit" class="btn btn-primary" id="Insert" style="align:right; display:none">Submit</button>            </div></div>

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
<?php include 'includes/footer.php'; // Include the footer file ?>
</body>
</html>

