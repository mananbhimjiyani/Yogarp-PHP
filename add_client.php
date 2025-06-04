<?php
ob_start();
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';
// Check if the user is logged in by verifying a session variable, such as 'user_id'
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to index.php
    header("Location: login.php");
    exit(); // Ensure no further code is executed after redirection
}
$formData = [
    'title' => '',
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'fullPhoneNumber' => '',
    'email' => '',
    'address1' => '',
    'address2' => '',
    'city' => '',
    'state' => '',
    'country' => '',
    'pin' => '',
    'enquiry_id' => '',
];

// Check if an enquiry has been selected and fetch its data
if (isset($_POST['enquiry_id'])) {
    $enquiry_id = $_POST['enquiry_id'];

    // Fetch enquiry data from the database
    $sql = "SELECT * FROM enquiry WHERE enquiry_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $enquiry_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $formData = $result->fetch_assoc();
		 // Format enquiry date
        if (!empty($formData['created_at'])) {
            $createdAt = new DateTime($formData['created_at']);
            $formData['enquiry_date'] = $createdAt->format('d/m/Y'); // Format to dd/mm/yyyy
        }

        // Now check demo_id in the demo table for the selected enquiry_id
        if (!empty($formData['enquiry_id'])) {
            $enquiry_id = $formData['enquiry_id'];

            // Prepare to fetch the demo ID based on enquiry ID
            $demo_stmt = $conn->prepare("SELECT * FROM demo WHERE enquiry_id = ?");
            $demo_stmt->bind_param("s", $enquiry_id);
            $demo_stmt->execute();
            $demo_result = $demo_stmt->get_result();

            // If a demo ID is found, fetch it
            if ($demo_result->num_rows > 0) {
                $demo_row = $demo_result->fetch_assoc();
                $formData['demo_id'] = $demo_row['demo_id']; // Store the demo_id
                $formData['studio_id'] = $demo_row['studio_id']; // Store the studio_id
                $formData['batch_id'] = $demo_row['batch_id']; // Store the batch_id
                $demoDate = new DateTime($demo_row['demo_date']);
                $formData['demo_date'] = $demoDate->format('d/m/Y');
            }
            
            // Prepare to fetch studio name based on studio_id from enquiry
            if (!empty($formData['studio_id'])) {
                $studio_id = $formData['studio_id'];
                $studio_stmt = $conn->prepare("SELECT studio_name AS studio_name FROM studio WHERE studio_id = ?");
                $studio_stmt->bind_param("s", $studio_id);
                $studio_stmt->execute();
                $studio_result = $studio_stmt->get_result();

                // If a studio name is found, fetch it
                if ($studio_result->num_rows > 0) {
                    $studio_row = $studio_result->fetch_assoc();
                    $formData['studio_name'] = $studio_row['studio_name']; // Store the studio_name
                }
               
            }

            // Prepare to fetch batch name based on batch_id from enquiry
            if (!empty($formData['batch_id'])) {
                $batch_id = $formData['batch_id'];
                $batch_stmt = $conn->prepare("SELECT batch_name AS batch_name FROM batch WHERE batch_id = ?");
                $batch_stmt->bind_param("s", $batch_id);
                $batch_stmt->execute();
                $batch_result = $batch_stmt->get_result();

                // If a batch name is found, fetch it
                if ($batch_result->num_rows > 0) {
                    $batch_row = $batch_result->fetch_assoc();
                    $formData['batch_name'] = $batch_row['batch_name']; // Store the batch_name
                }
                
            }
        }
		
    } else {
        echo "<script>alert('No record found for this search term.');</script>";
    }
    
}
  // SQL query to fetch ISO, CallCode, CountryName, and PhoneLength from CountryCode table
    $CallCodesql = "SELECT ISO, CONCAT('+', CallCode) AS CallCode, CountryName, PhoneLength FROM CountryCode";
    $result = $conn->query($CallCodesql);

    // Initialize options variable
    $options = '';

    // Check if there are results
    if ($result->num_rows > 0) {
        // Loop through the results and create option elements with data-phone-length
        while ($row = $result->fetch_assoc()) {
            $selected = ($row['CallCode'] == '91') ? 'selected' : ''; // Default India
            $options .= '<option value="' . htmlspecialchars($row['CallCode'] ?? '') . '" ' . $selected . 
                        ' data-phone-length="' . htmlspecialchars($row['PhoneLength'] ?? '') . '">' .
                        htmlspecialchars($row['CallCode'] ?? '') . ' - ' . 
                        htmlspecialchars($row['ISO'] ?? '') . 
                        ' (' . htmlspecialchars($row['CountryName'] ?? '') . ')</option>';
        }
    } else {
        $options .= '<option value="">No Country Codes Available</option>';
    }
  
// SQL query to get the enquiry details for the dropdown
$sql2 = "
    SELECT e.title, e.first_name, e.middle_name, e.last_name, e.fullPhoneNumber, e.enquiry_id
    FROM enquiry e
    WHERE e.enquiry_id IN (
        SELECT enquiry_id 
        FROM demo 
        WHERE enquiry_id NOT IN (SELECT enquiry_id FROM clients) 
          AND demo_id NOT IN (SELECT enquiry_id FROM clients)
          AND active = 2
    );
";

$result = $conn->query($sql2);

$sq2 = "SELECT MAX(client_id) AS max_client_id FROM clients"; // Added semicolon at the end
$max_row = $conn->query($sq2);

// Check if the query was successful
if ($max_row) {
    // Fetch the row as an associative array
    $row = $max_row->fetch_assoc();

    // Get max_client_id or null if no clients exist
    $maxClientId = $row['max_client_id'] ?? null; 
	$newClientId = $maxClientId + 1;
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_client'])) {
    // Validate the form ID
    if (isset($_POST['form_id']) && $_POST['form_id'] === 'client_data_entry') {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Sanitize form input safely using null coalescing
            $title = trim($_POST['title'] ?? '');
            $first_name = trim($_POST['first_name'] ?? '');
            $middle_name = trim($_POST['middle_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $fullPhoneNumber = trim($_POST['fullPhoneNumber'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address1 = trim($_POST['address1'] ?? '');
            $address2 = trim($_POST['address2'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $country = trim($_POST['country'] ?? '');
            $pin = trim($_POST['pin'] ?? '');
            $studio_id = trim($_POST['studio_id'] ?? '');
            $batch_id = trim($_POST['batch_id'] ?? '');
            $enquiry_id = trim($_POST['enquiry_id'] ?? '');
            $demo_id = trim($_POST['demo_id'] ?? '');
            $photo = '';

            // Handle file upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
                $max_stmt = $conn->query("SELECT MAX(client_id) AS max_client_id FROM clients");
                $max_row = $max_stmt->fetch_assoc();
                $maxClientId = $max_row['max_client_id'] ?? 0;
                $newClientId = $maxClientId + 1;

                $fileTmpPath = $_FILES['photo']['tmp_name'];
                $fileName = $_FILES['photo']['name'];
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $photo = "client_$newClientId.$fileExtension";
            }

            // Insert into clients table
            $stmt = $conn->prepare("INSERT INTO clients 
                (title, first_name, middle_name, last_name, fullPhoneNumber, email, address1, address2, city, state, country, pin, studio_id, batch_id, photo, demo_id, enquiry_id, stamp)
                VALUES (?, ?, ?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssssssssssiisii", $title, $first_name, $middle_name, $last_name, $fullPhoneNumber, $email, $address1, $address2, $city, $state, $country, $pin, $studio_id, $batch_id, $photo, $demo_id, $enquiry_id);

            if (!$stmt->execute()) {
                throw new Exception("Error inserting client data: " . $stmt->error);
            }
            $client_id = $stmt->insert_id;

            // Move uploaded file if client insertion is successful
            if (!empty($photo)) {
                $uploadFileDir = 'uploads/photo/client/';
                $dest_path = $uploadFileDir . $photo;
                if (!move_uploaded_file($fileTmpPath, $dest_path)) {
                    throw new Exception("Error moving uploaded file.");
                }
            }

            // Insert into emergency_contacts table
            if (!empty($_POST['emergency_contact_name']) && !empty($_POST['emergency_contact_number'])) {
                $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
                $callCodeEmergency = trim($_POST['CallCode_emergency_contact_number'] ?? ''); // Handle null safely
                $emergencyContactNumber = trim($_POST['emergency_contact_number'] ?? '');
                $combinedEmergencyContactNumber = $callCodeEmergency . $emergencyContactNumber;

                $stmt_contact = $conn->prepare("INSERT INTO emergency_contacts (client_id, emergency_contact_name, combinedEmergencyContactNumber) VALUES (?, ?, ?)");
                $stmt_contact->bind_param("iss", $client_id, $emergency_contact_name, $combinedEmergencyContactNumber);

                if (!$stmt_contact->execute()) {
                    throw new Exception("Error inserting emergency contact data: " . $stmt_contact->error);
                }
                $stmt_contact->close();
            }

            // Insert into professional_info table
            $profession = trim($_POST['profession'] ?? '');
            $mode_of_work = trim($_POST['mode_of_work'] ?? '');
            $company_name = trim($_POST['company_name'] ?? '');
            $work_address1 = trim($_POST['work_address1'] ?? '');
            $work_address2 = trim($_POST['work_address2'] ?? '');
            $work_city = trim($_POST['work_city'] ?? '');
            $work_state = trim($_POST['work_state'] ?? '');
            $work_country = trim($_POST['work_country'] ?? '');
            $work_pin = trim($_POST['work_pin'] ?? '');

            $stmt_prof = $conn->prepare("INSERT INTO professional_info (client_id, profession, mode_of_work, company_name, address1, address2, city, state, country, pin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_prof->bind_param("isssssssss", $client_id, $profession, $mode_of_work, $company_name, $work_address1, $work_address2, $work_city, $work_state, $work_country, $work_pin);

            if (!$stmt_prof->execute()) {
                throw new Exception("Error inserting professional info: " . $stmt_prof->error);
            }
            $stmt_prof->close();

            // Insert into family_info table
            $title_Family = trim($_POST['title_Family'] ?? '');
            $family_first_name = trim($_POST['Family_first_name'] ?? '');
            $family_last_name = trim($_POST['Family_last_name'] ?? '');
            $family_relationship = trim($_POST['Family_relationship'] ?? '');
            $family_callCode = trim($_POST['Family_callCode'] ?? ''); // Handle null safely
            $family_contact_number = trim($_POST['Family_contact_number'] ?? '');
            $family_contact = $family_callCode . $family_contact_number;
            $family_email = trim($_POST['Family_email'] ?? '');
            $family_address1 = trim($_POST['Family_address1'] ?? '');
            $family_address2 = trim($_POST['Family_address2'] ?? '');
            $family_city = trim($_POST['Family_city'] ?? '');
            $family_state = trim($_POST['Family_state'] ?? '');
            $family_country = trim($_POST['Family_country'] ?? '');
            $family_pin = trim($_POST['family_pin'] ?? '');
            $client_id = $stmt->insert_id; // Get the last inserted client_id

            // Prepare the insert statement
            $stmt_insert = $conn->prepare("INSERT INTO family_info (family_title, family_first_name, family_last_name, family_relationship, family_contact_number, family_email, family_address1, family_address2, family_city, family_state, family_country, family_pin, client_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("ssssssssssssi", $title_Family, $family_first_name, $family_last_name, $family_relationship, $family_contact, $family_email, $family_address1, $family_address2, $family_city, $family_state, $family_country, $family_pin, $client_id);

            if (!$stmt_insert->execute()) {
                throw new Exception("Error inserting family info: " . $stmt_insert->error);
            }

            // Commit transaction if all inserts are successful
            $conn->commit();
            echo "Client data successfully inserted.";

            // Close statements
            $stmt->close();
            $stmt_insert->close(); // Close family info statement
        } catch (Exception $e) {
            // Rollback transaction if any error occurs
            $conn->rollback();
            echo "Transaction failed: " . $e->getMessage();
        }
    } else {
        echo "Form ID is missing or invalid.";
    }
}

ob_end_flush();
?>

<h1>Add Client</h1>
<div class="search-container">
<div class="container">
    <form method="post">
        <div class="row">
            <div class="col-md-6">
                <label for="enquirySelect">Select Enquiry</label>
                <select class="form-control" id="enquirySelect" name="enquiry_id" onchange="this.form.submit()" required>
                    <option value="">-- Select Enquiry --</option>
                    <?php
                    // Fetching and displaying each result in the dropdown
                    while ($row = $result->fetch_assoc()) {
                        $fullName = $row['title'] . ' ' . $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'];
                        $selected = (isset($formData['enquiry_id']) && $formData['enquiry_id'] == $row['enquiry_id']) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($row['enquiry_id']) . '" ' . $selected . '>' . htmlspecialchars($fullName) . ' (' . htmlspecialchars($row['fullPhoneNumber']) . ')</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
    </form>
 <form id="client_data_entry" method="POST" enctype="multipart/form-data">
    <div class="row" style="padding-bottom:10px">
        <div class="col-md-2">
            <label for="title" class="form-label"><span style="color:red">* </span>Title</label>
            <select class="form-select" id="title" name="title" required>
                <option value="" disabled>Select a title</option>
                <option value="Mr" <?= $formData['title'] === 'Mr' ? 'selected' : '' ?>>Mr.</option>
                <option value="Mrs" <?= $formData['title'] === 'Mrs' ? 'selected' : '' ?>>Mrs.</option>
                <option value="Ms" <?= $formData['title'] === 'Ms' ? 'selected' : '' ?>>Ms.</option>
                <option value="Miss" <?= $formData['title'] === 'Miss' ? 'selected' : '' ?>>Miss</option>
                <option value="Master" <?= $formData['title'] === 'Master' ? 'selected' : '' ?>>Master</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="first_name" class="form-label"><span style="color:red">* </span>First Name</label>
            <input type="text" class="form-control" name="first_name" id="first_name" oninput="capitalizeFirstLetter(this)" 
            value="<?= htmlspecialchars($formData['first_name'] ?? '') ?>" required>
        </div>
        <div class="col-md-2">
            <label for="middle_name" class="form-label">Middle Name</label>
            <input type="text" class="form-control" name="middle_name" id="middle_name" oninput="capitalizeFirstLetter(this)" 
            value="<?= htmlspecialchars($formData['middle_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label for="last_name" class="form-label"><span style="color:red">* </span>Last Name</label>
            <input type="text" class="form-control" name="last_name" id="last_name" oninput="capitalizeFirstLetter(this)" 
            value="<?= htmlspecialchars($formData['last_name'] ?? '') ?>" required>
        </div>
    </div>

<div class="row"style="padding-bottom:10px">
        <div class="col-md-6">
            <label for="fullPhoneNumber" class="form-label"><span style="color:red">* </span>Mobile Number:</label>
            <input type="tel" class="form-control" name="fullPhoneNumber" id="fullPhoneNumber" value="<?= htmlspecialchars($formData['fullPhoneNumber'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label for="email" class="form-label">Email:</label>
            <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
        </div>
    </div>
<div class="row"style="padding-bottom:10px">
        <div class="col-md-6">
            <label for="address1" class="form-label"><span style="color:red">* </span>Address:</label>
            <input type="text" class="form-control" name="address1"            oninput="capitalizeFirstLetter(this)" 
id="address1" value="<?= htmlspecialchars($formData['address1'] ?? '') ?>"required>
        </div>
        <div class="col-md-6">
            <label for="address2" class="form-label">Additional Address:</label>
            <input type="text" class="form-control" name="address2"            oninput="capitalizeFirstLetter(this)" 
id="address2" value="<?= htmlspecialchars($formData['address2'] ?? '') ?>">
        </div>
    </div>
	   <div class="row"style="padding-bottom:10px">
    <div class="col-md-3">
    <label for="city" class="form-label"><span style="color:red">* </span>City:</label>
    <input type="text" name="city" id="city" class="form-control" 
           onkeyup="fetchCities(this.value)" 
           oninput="capitalizeFirstLetter(this)" 
           value="<?= htmlspecialchars($formData['city'] ?? '') ?>" required>
    <ul id="citySuggestions" class="list-group" style="position: absolute; z-index: 1000; display: none;"></ul> <!-- Dropdown suggestions -->
</div>

				        <div class="col-md-3">
            <label for="state" class="form-label"><span style="color:red">* </span>State:</label>
            <input type="text" name="state" id="state"            oninput="capitalizeFirstLetter(this)" 
class="form-control" value="<?= htmlspecialchars($formData['state'] ?? '') ?>">
			
        </div>
	        <div class="col-md-3">
            <label for="country" class="form-label"><span style="color:red">* </span>Country:</label>
            <input type="text" name="country"            oninput="capitalizeFirstLetter(this)" 
id="country" class="form-control" value="<?= htmlspecialchars($formData['country'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label for="pin" class="form-label">PIN/Zip:</label>
            <input type="text" name="pin" id="pin" class="form-control" value="<?= htmlspecialchars($formData['pin'] ?? '') ?>">
        </div>
    </div>
<div class="row"style="padding-bottom:10px">
    <div class="col-md-6">
        <div class="col-md-12">
            <label for="photo" class="form-label"><span style="color:red">* </span>Photo:</label>
            <input type="file" class="form-control" name="photo" id="photo" accept="image/*" capture="environment" onchange="handleFileUpload(event)">
        </div>
        <div class="col-md-12">
            <!-- Container for image preview -->
            <div id="imagePreviewContainer">
                <img id="imagePreview" src="" alt="Photo Preview" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 2px solid #ccc;">
            </div>
        </div>
    </div>
<script>
    function toggleAnniversaryField() {
        const maritalStatus = document.getElementById('maritalStatus').value;
        const anniversaryContainer = document.getElementById('anniversaryContainer');

        if (maritalStatus === 'Married') {
            anniversaryContainer.style.display = 'block'; // Show the anniversary date field
        } else {
            anniversaryContainer.style.display = 'none'; // Hide the anniversary date field
        }
    }

    function handleFileUpload(event) {
        const file = event.target.files[0];

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgPreview = document.getElementById('imagePreview');
                imgPreview.src = e.target.result;

                // Show the image preview container
                document.getElementById('imagePreviewContainer').style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            // If no file is selected, hide the preview
            document.getElementById('imagePreviewContainer').style.display = 'none';
        }
    }

    // Example title; this should be dynamically set based on your application logic
    const title = 'Mr.'; // Change this value to test
    setAvatarImage(title);
</script>
    <div class="col-md-6">
<div class="row"style="padding-bottom:10px">
           
            <div class="col-md-6" id="height">
                <label for="height" class="form-label">Height:</label>
                <input type="number" name="height" id="height" class="form-control">
            </div>
            <div class="col-md-6" id="weight">
                <label for="weight" class="form-label">Weight:</label>
                <input type="number" name="weight" id="weight" class="form-control">
            </div>
        
		       <div class="row"style="padding-bottom:10px">
<div class="col-md-6" id="dob">
    <label for="dob" class="form-label">Birthdate:</label>
    <label id="Age"></label>
    <input type="date" name="dob" id="dob" class="form-control" value="<?= htmlspecialchars($formData['dob'] ?? '') ?>">
</div>

<script>
    // Function to calculate age based on the date of birth
    function calculateAge(dob) {
        var birthDate = new Date(dob);
        var today = new Date();
        var age = today.getFullYear() - birthDate.getFullYear();
        var monthDiff = today.getMonth() - birthDate.getMonth();

        // If birth date has not occurred yet this year, subtract one from the age
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age;
    }

    // Function to update age on page load and on date change
    function updateAge() {
        var dob = document.getElementById('dob').value;
        if (dob) {
            var age = calculateAge(dob);
            document.getElementById('Age').innerText = age + " Years";
        } else {
            document.getElementById('Age').innerText = ""; // Clear if no date is selected
        }
    }

    // Add event listener to the date input for 'change' event
    document.getElementById('dob').addEventListener('change', updateAge);

    // Call updateAge on page load to set initial age
    window.onload = updateAge;
</script>
			</div>
<div class="row"style="padding-bottom:10px">
            <div class="col-md-6">
                <label for="maritalStatus" class="form-label">Marital Status:</label>
                <select class="form-select" id="maritalStatus" name="marital_status" required onchange="toggleAnniversaryField()">
                    <option value="" disabled selected>Select a Marital Status</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Divorced">Divorced</option>
                    <option value="Not">Not want to disclose</option>
                </select>
            </div>
            <div class="col-md-6" id="anniversaryContainer" style="display: none;">
                <label for="anniversary_date" class="form-label">Anniversary Date:</label>
                <input type="date" name="anniversary_date" id="anniversary_date" class="form-control">
            </div>
        </div>
    </div>
</div>


<div class="row"style="padding-bottom:10px">
        <h2>Emergency Contacts</h2>
<div class="col-md-1">
                        <label for="title"class="form-label"><span style="color:red">* </span>Title:</label>
                        <select class="form-control" id="title" name="title" required onchange="updateGender()">
					  <option value="" disabled selected>Select a title</option>
                            <option value="Mr.">Mr.</option>
                            <option value="Mrs.">Mrs.</option>
                            <option value="Ms.">Ms.</option>
                            <option value="Miss">Miss</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
    <div class="col-md-4">

                <label for="emergency_contact_name"class="form-label"><span style="color:red">* </span>Contact Name:</label>
                <input type="text" class="form-control" name="emergency_contact_name" id="emergency_contact_name" oninput="capitalizeFirstLetter(this)"required>
				</div>    
				 <div class="col-md-2">
            <label for="CallCode_emergency_contact_number"class="form-label"><span style="color:red">* </span>Country Code</label>
            <select class="form-control" id="CallCode_emergency_contact_number" name="CallCode" required>
                <option value="">Select  Code</option>
                <?php echo $options; // Populate options here ?>
            </select>
        </div>
	   		
				<div class="col-md-3">
    <label for="emergency_contact_number" class="form-label"><span style="color:red">* </span>Contact Number:</label>
    <input type="tel" class="form-control" id="emergency_contact_number" name="emergency_contact_number" pattern="[0-9]*" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
    <small id="phoneError" style="color:red; display:none;">Phone number does not match the required length.</small>
</div>
 <div class="col-md-2">

                <label for="emergency_contact_relationship"class="form-label"><span style="color:red">* </span>Relationship:</label>
                 <select class="form-control" id="emergency_contact_relationship" name="emergency_contact_relationship" required>
					  <option value="" disabled selected>Select </option>
                            <option value="Spouse">Spouse</option>
                            <option value="Parents">Parents</option>
                            <option value="Child">Child</option>
							<option value="Relative">Siblings</option>
                            <option value="Friend">Friend</option>
                            <option value="Relative">Relative</option>
							<option value="Other">Other`</option>
                        </select>
				</div>	
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    // Function to validate phone number length based on selected country code
    function validatePhoneLength() {
        // Get the selected option from CallCode
        var selectedOption = $('#CallCode option:selected');
        var phoneLength = selectedOption.data('phone-length'); // Get phone length from data attribute
        var mobileNumber = $('#emergency_contact_number').val(); // Get entered mobile number

        // Debugging statements for tracking values
        console.log("Selected phone length: ", phoneLength);
        console.log("Entered mobile number length: ", mobileNumber.length);

        // Compare phone length with mobile number length
        if (phoneLength && mobileNumber.length !== phoneLength) {
            $('#phoneError').show(); // Show error if length doesn't match
            return false;
        } else {
            $('#phoneError').hide(); // Hide error if length matches
            return true;
        }
    }

    $(document).ready(function() {
        // Validate phone number length while typing in the mobile number field
        $('#emergency_contact_number').on('input', function() {
            console.log("Typing in mobile number...");
            validatePhoneLength(); // Validate on input
        });

        // Also validate when the country code changes
        $('#CallCode').on('change', function() {
            console.log("Country code changed...");
            validatePhoneLength(); // Validate when country code changes
        });

        // Initial validation on page load
        validatePhoneLength();
    });
</script>
</div>
<div class="row"style="padding-bottom:10px">

<h2>Professional Information</h2>
<div class="col-md-6">
        <label for="profession" class="form-label">Title</label>
        <select class="form-select" id="profession" name="profession" required onchange="handleProfessionChange()">
            <option value="" disabled selected>Select Profession</option>
            <option value="Business">Business</option>
            <option value="Job">Job</option>
            <option value="Doctor">Doctor</option>
			<option value="Retired">Retired</option>
            <option value="Housewife">Housewife</option>
        </select>
    </div>          

 <div class="col-md-6">
    <label for="mode_of_work"class="form-label"><span style="color:red">* </span>Mode of Work:</label>
    <select name="mode_of_work" class="form-control" id="mode_of_work" required>
        <option value="" disabled <?= empty($formData['mode_of_work']) ? 'selected' : '' ?>>Select Mode of Work</option>
        <option value="Sitting" <?= (isset($formData['mode_of_work']) && $formData['mode_of_work'] === 'Sitting') ? 'selected' : '' ?>>Sitting</option>
        <option value="Standing" <?= (isset($formData['mode_of_work']) && $formData['mode_of_work'] === 'Standing') ? 'selected' : '' ?>>Standing</option>
        <option value="Traveling" <?= (isset($formData['mode_of_work']) && $formData['mode_of_work'] === 'Traveling') ? 'selected' : '' ?>>Traveling</option>
        <option value="Unscheduled" <?= (isset($formData['mode_of_work']) && $formData['mode_of_work'] === 'Unscheduled') ? 'selected' : '' ?>>Unscheduled</option>
        <option value="Mix Mode" <?= (isset($formData['mode_of_work']) && $formData['mode_of_work'] === 'Mix Mode') ? 'selected' : '' ?>>Mix Mode</option>
    </select>
</div>
     <div id="Housewife">
        <div class="col-md-12">
            <label for="company_name" class="form-label"><span style="color:red">* </span>Company Name:</label>
            <input type="text" name="company_name" id="company_name" class="form-control" oninput="capitalizeFirstLetter(this)" required>
        </div>

        <div class="row" style="padding-bottom:10px">
            <div class="col-md-6">
                <label for="work_address1" class="form-label"><span style="color:red">* </span>Address 1:</label>
                <input type="text" name="work_address1" id="work_address1" class="form-control" oninput="capitalizeFirstLetter(this)" required>
            </div>
            <div class="col-md-6">
                <label for="work_address2" class="form-label">Additional Address:</label>
                <input type="text" name="work_address2" id="work_address2" class="form-control" oninput="capitalizeFirstLetter(this)">
            </div>
        </div>

        <div class="row" style="padding-bottom:10px">
            <div class="col-md-3">
                <label for="work_city" class="form-label"><span style="color:red">* </span>City:</label>
                <input type="text" name="work_city" id="work_city" class="form-control" oninput="capitalizeFirstLetter(this)" required>
            </div>
            <div class="col-md-3">
                <label for="work_state" class="form-label"><span style="color:red">* </span>State:</label>
                <input type="text" name="work_state" id="work_state" class="form-control" oninput="capitalizeFirstLetter(this)" required>
            </div>
            <div class="col-md-3">
                <label for="work_country" class="form-label"><span style="color:red">* </span>Country:</label>
                <input type="text" name="work_country" id="work_country" class="form-control" oninput="capitalizeFirstLetter(this)" required>
            </div>
            <div class="col-md-3">
                <label for="work_pin" class="form-label">PIN:</label>
                <input type="text" name="work_pin" id="work_pin" class="form-control">
            </div>
        </div>
    </div>
</div>

<script>
    function handleProfessionChange() {
        var profession = document.getElementById('Profession').value;
        var housewifeSection = document.getElementById('Housewife');
        var inputs = housewifeSection.querySelectorAll('.form-control');

        // Check if the profession is Housewife or Retired
        if (profession === 'Housewife' || profession === 'Retired') {
            // Hide the Housewife section
            housewifeSection.style.display = 'none';

            // Remove required attributes from the inputs
            inputs.forEach(function(input) {
                input.required = false;
            });
        } else {
            // Show the Housewife section
            housewifeSection.style.display = 'block';

            // Add required attributes back to the inputs
            inputs.forEach(function(input) {
                if (input.name !== 'professional_address2' && input.name !== 'professional_pin') {
                    input.required = true;
                }
            });
        }
    }

    // Call the function on page load in case the form is pre-populated
    window.onload = function() {
        handleProfessionChange();
    };
</script>
	<div class="row"style="padding-bottom:10px">

 <h2>Family Information</h2>
		
<div class="row mb-3">
    <div class="col-md-2">
        <label for="title_Family" class="form-label">Title</label>
        <select class="form-select" id="title_Family" name="title_Family" required>
            <option value="" disabled selected>Select a title</option>
            <option value="Mr">Mr.</option>
            <option value="Mrs">Mrs.</option>
            <option value="Miss">Miss</option>
            <option value="Master">Master</option>
        </select>
    </div>
    <div class="col-md-5">
        <label for="Family_first_name" class="form-label">First Name</label>
        <input type="text" class="form-control" name="Family_first_name" id="Family_first_name" required>
    </div>
    <div class="col-md-5">
        <label for="Family_last_name" class="form-label">Last Name</label>
        <input type="text" class="form-control" name="Family_last_name" id="Family_last_name" required>
    </div>
</div>
<div class="row mb-3">
<div class="col-md-2">

                <label for="Family_relationship"class="form-label"><span style="color:red">* </span>Relationship:</label>
                 <select class="form-control" id="Family_relationship" name="Family_relationship" required>
					  <option value="" disabled selected>Select </option>
                            <option value="Spouse">Spouse</option>
                            <option value="Parents">Parents</option>
                            <option value="Child">Child</option>
							<option value="Relative">Siblings</option>
                            <option value="Friend">Friend</option>
                            <option value="Relative">Relative</option>
							<option value="Other">Other`</option>
                        </select>
				</div>	

     <div class="col-md-2">
            <label for="Family_callCode"class="form-label"><span style="color:red">* </span>Country Code</label>
            <select class="form-control" id="Family_callCode" name="CallCode" required>
                <option value="">Select  Code</option>
                <?php echo $options; // Populate options here ?>
            </select>
        </div>
	   	
				<div class="col-md-3">
    <label for="emergency_contact_number" class="form-label"><span style="color:red">* </span>Contact Number:</label>
    <input type="tel" class="form-control" id="Family_contact_number" name="emergency_contact_number" pattern="[0-9]*" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
    <small id="phoneError" style="color:red; display:none;">Phone number does not match the required length.</small>
</div>
    <div class="col-md-4">
        <label for="family_email" class="form-label">Email:</label>
        <input type="email" class="form-control" name="family_email" id="family_email">
    </div>
</div>    
<div class="row mb-3">
    <div class="col-md-6">
        <label for="Family_address1" class="form-label"><span style="color:red">* </span>Address:</label>
        <input type="text" class="form-control" name="Family_address1" id="Family_address1"required>
    </div>
    <div class="col-md-6">
        <label for="Family_address2" class="form-label">Additional Address:</label>
        <input type="text" class="form-control" name="Family_address2" id="Family_address2">
    </div>
</div>
<div class="row mb-3">
   <div class="col-md-3">
			<label for="Family_city"class="form-label"><span style="color:red">* </span>City:</label>
			<input type="text" name="Family_city" id="Family_city"class="form-control"
			onkeyup="fetchSpouseCities(this.value)" 
           oninput="capitalizeFirstLetter(this)" 
           value="<?= htmlspecialchars($formData['Family_city'] ?? '') ?>" required>
    <ul id="citySuggestions" class="list-group" style="position: absolute; z-index: 1000; display: none;"></ul> <!-- Dropdown suggestions -->
			
			</div>
			<div class="col-md-3">

			<label for="Family_state"class="form-label"><span style="color:red">* </span>State:</label>
			<input type="text" name="Family_state" id="Family_state"class="form-control"oninput="capitalizeFirstLetter(this)" 
class="form-control" value="<?= htmlspecialchars($formData['Family_state'] ?? '') ?>"required>
</div>
			<div class="col-md-3">

			<label for="Family_country"class="form-label"><span style="color:red">* </span>Country:</label>
			<input type="text" name="Family_country" id="Family_country"class="form-control"oninput="capitalizeFirstLetter(this)" 
id="country" class="form-control" value="<?= htmlspecialchars($formData['Family_country'] ?? '') ?>"required></div>
			<div class="col-md-3">
			<label for="family_pin"class="form-label">PIN:</label>
			<input type="text" name="family_pin" id="family_pin"class="form-control"></div>
</div>

</div>		


<div class="terms-and-conditions">
<h4>Terms and Conditions</h4>

<h5>Injury Waiver</h5>
<p>
    I acknowledge that yoga involves physical activity and may carry the risk of injury. I understand that it is my responsibility to consult with a healthcare provider to ensure that I am physically and mentally fit to participate in yoga classes. I voluntarily assume all risks associated with yoga practice and agree to release and hold harmless the instructor, the studio, and any related parties from any liability for injuries that may occur during participation.
</p>

<h5>No Guarantee of Results</h5>
<p>
    I understand that yoga practice results can vary depending on individual factors, including pre-existing health conditions, level of physical fitness, consistency, and personal response to the practice. There are no guarantees regarding specific outcomes from participation in yoga classes.
</p>

<h5>Personal Responsibility</h5>
<p>
    I accept full responsibility for the safekeeping of my personal belongings during yoga sessions. The studio and instructors are not liable for any loss or damage to personal items.
</p>

<h5>No Refund Policy</h5>
<p>
    I understand that all payments for yoga classes are final and non-refundable. In the event of a cancellation due to personal reasons such as a family emergency, space issues, or illness, I acknowledge that I will not receive a refund, credit, or voucher for any missed classes.
</p>

<h5>Photo and Video Consent</h5>
<p>
    I consent to the use of photographs or video recordings taken during yoga classes for promotional purposes, including use on websites, social media, or other marketing materials.
</p>

<p>
    By agreeing to these terms and conditions, I confirm my understanding and acceptance of the policies outlined above.
</p>

    
    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="termsCheck" required>
        <label class="form-check-label" for="termsCheck"class="form-label">I Agree To The Terms And Conditions Stated Above, As Well As Any Future Updates Or Changes, Whether Known Or Unknown of Studio.</label>
    </div>
</div>
    <div class="row mb-3"style="margin-top:10px">
    <input type="hidden" name="form_id" value="client_data_entry">

    <button type="submit" class="btn btn-primary" name="submit_client">Submit</button>
</div>

<div class="row mb-3" style="margin-top: 10px;"></div>
<h5 style="text-align: center;">Office Data</h5>
<hr style="border: 1px solid #a3a3a3 ;">
		 <div class="row mb-3">
		 <div class="col-md-3">
        <label for="enquiry_id" class="form-label">Enquiry ID:</label>
        <input type="text" class="form-control" name="enquiry_id" id="enquiry_id" value="<?= htmlspecialchars($formData['enquiry_id'] ?? '') ?>" readonly>
        </div>
		
		<div class="col-md-3">
        <!-- Display Enquiry Date -->
        <label for="enquiry_date" class="form-label">Enquiry Date:</label>
        <input type="text" class="form-control" name="enquiry_date" id="enquiry_date" value="<?= htmlspecialchars($formData['enquiry_date'] ?? '') ?>" readonly>
    </div>
	<div class="col-md-3">
        <!-- Display Enquiry Date -->
        <label for="client_id" class="form-label">Client ID:</label>
                     
<input type="text" class="form-control" name="client_id" id="client_id" value="<?= htmlspecialchars($newClientId ?? '0') ?>" readonly>  </div>
	<div class="col-md-3">
        <!-- Display Enquiry Date -->
        <label for="reference" class="form-label">Reference:</label>
        <input type="text" class="form-control" name="reference" id="reference" value="<?= htmlspecialchars($formData['reference'] ?? '') ?>" readonly>
    </div>
    </div>
			 <div class="row mb-3">
			  <div class="col-md-3">
        <label for="demo_id" class="form-label">Demo ID:</label>
        <input type="text" class="form-control" name="demo_id" id="demo_id" value="<?= htmlspecialchars($formData['demo_id'] ?? '') ?>" readonly>
       </div> <div class="col-md-3">
        <!-- Display Demo Date -->
        <label for="demo_date" class="form-label">Demo Date:</label>
        <input type="text" class="form-control" name="demo_date" id="demo_date" value="<?= htmlspecialchars($formData['demo_date'] ?? '') ?>" readonly>
    </div>
		 <div class="col-md-3">
            <label for="batch_name"class="form-label">Batch ID:</label>
            <input type="text" class="form-control"name="batch_name" id="batch_name" value="<?= htmlspecialchars($formData['batch_name'] ?? '') ?>"readonly>
            <input type="hidden" class="form-control"name="batch_id" id="batch_id" value="<?= htmlspecialchars($formData['batch_id'] ?? '') ?>"readonly>
		</div>
		  <div class="col-md-3">
            <label for="studio_name"class="form-label">Studio ID:</label>
            <input type="text" class="form-control"name="studio_name" id="studio_name" value="<?= htmlspecialchars($formData['studio_name'] ?? '') ?>"readonly>
			<input type="hidden" class="form-control"name="studio_id" id="studio_id" value="<?= htmlspecialchars($formData['studio_id'] ?? '') ?>"readonly>
        </div>
</div>
    </div>
</form>

<script>
function fetchCities(city) {
                    // AJAX to fetch states and countries based on the city input
                    // Replace the URL with your appropriate endpoint
                    fetch(`fetch_cities.php?city=${city}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.length) {
                                const state = data[0].state; // Assuming first result's state
                                const country = data[0].country; // Assuming first result's country

                                document.getElementById('state').value = state;
                                document.getElementById('country').value = country;
                            } else {
                                // Clear the fields if no data found
                                document.getElementById('state').value = '';
                                document.getElementById('country').value = '';
                            }
                        });
                }
				
function capitalizeFirstLetter(input) {
    const value = input.value;
    if (value.length > 0) {
        // Split the string into words, capitalize each word, then join them back with spaces
        input.value = value
            .toLowerCase()
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    }
}

</script>
<script>
function fetchProfessionalCities(city) {
                    // AJAX to fetch states and countries based on the city input
                    // Replace the URL with your appropriate endpoint
                    fetch(`fetch_cities.php?city=${city}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.length) {
                                const state = data[0].state; // Assuming first result's state
                                const country = data[0].country; // Assuming first result's country

                                document.getElementById('work_state').value = state;
                                document.getElementById('work_country').value = country;
                            } else {
                                // Clear the fields if no data found
                                document.getElementById('work_state').value = '';
                                document.getElementById('work_country').value = '';
                            }
                        });
                }
				
function capitalizeFirstLetter(input) {
    const value = input.value;
    if (value.length > 0) {
        // Split the string into words, capitalize each word, then join them back with spaces
        input.value = value
            .toLowerCase()
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    }
}

</script>
<script>
function fetchSpouseCities(city) {
                    // AJAX to fetch states and countries based on the city input
                    // Replace the URL with your appropriate endpoint
                    fetch(`fetch_cities.php?city=${city}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.length) {
                                const state = data[0].state; // Assuming first result's state
                                const country = data[0].country; // Assuming first result's country

                                document.getElementById('Family_state').value = state;
                                document.getElementById('Family_country').value = country;
                            } else {
                                // Clear the fields if no data found
                                document.getElementById('Family_state').value = '';
                                document.getElementById('Family_country').value = '';
                            }
                        });
                }
				
function capitalizeFirstLetter(input) {
    const value = input.value;
    if (value.length > 0) {
        // Split the string into words, capitalize each word, then join them back with spaces
        input.value = value
            .toLowerCase()
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    }
}

</script>


<?php include 'includes/footer.php'; // Include the footer file ?>
	