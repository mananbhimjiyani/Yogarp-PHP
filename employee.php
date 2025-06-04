<?php
// db.php - Database connection file
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';

// Initialize variables and error messages
$errors = [];
$success = "";

// Check if user is logged in

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize variables
$errors = [];
$success = false;
$lastEmployeeQuery = "SELECT MAX(id) AS lastEmployeeId FROM applicants";
$result = $conn->query($lastEmployeeQuery);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastEmployeeId = $row['lastEmployeeId'];
    $employeeId = $lastEmployeeId + 1;
} else {
    $employeeId = 1;
}
$uploadDir = 'uploads/';
$messages = [];

function uploadFile($file, $fieldName, $employeeId, $uploadDir) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $target_file = $uploadDir . $employeeId . '_' . $fieldName . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return $target_file;
        } else {
            return "Sorry, there was an error uploading your file for $fieldName.";
        }
    } else {
        return "File not uploaded or there was an error for $fieldName.";
    }
}

function mobileExists($conn, $mobile_number) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM applicants WHERE mobile_number = ?");
    $stmt->bind_param("s", $mobile_number);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mobile_number = $_POST['mobile_number'];
    if (mobileExists($conn, $mobile_number)) {
        echo "Mobile number already exists. Please use a different number.";
        $conn->close();
        exit;
    }

	$resume = isset($_FILES['resume']) ? uploadFile($_FILES['resume'], 'resume', $employeeId, $uploadDir) : false;
	$photo = isset($_FILES['photo']) ? uploadFile($_FILES['photo'], 'photo', $employeeId, $uploadDir) : false;
	$aadhar_card = isset($_FILES['aadhar_card']) ? uploadFile($_FILES['aadhar_card'], 'aadhar_card', $employeeId, $uploadDir) : false;
	$address_proof = isset($_FILES['address_proof']) ? uploadFile($_FILES['address_proof'], 'address_proof', $employeeId, $uploadDir) : false;
	$tenth = isset($_FILES['tenth']) ? uploadFile($_FILES['tenth'], 'tenth', $employeeId, $uploadDir) : false;
	$twelfth = isset($_FILES['twelfth']) ? uploadFile($_FILES['twelfth'], 'twelfth', $employeeId, $uploadDir) : false;
	$bachelor = isset($_FILES['bachelor']) ? uploadFile($_FILES['bachelor'], 'bachelor', $employeeId, $uploadDir) : false;
	$yoga_certification = isset($_FILES['yoga_certification']) ? uploadFile($_FILES['yoga_certification'], 'yoga_certification', $employeeId, $uploadDir) : false;
	$pan_card = isset($_FILES['pan_card']) ? uploadFile($_FILES['pan_card'], 'pan_card', $employeeId, $uploadDir) : false;
	$passport = isset($_FILES['passport']) ? uploadFile($_FILES['passport'], 'passport', $employeeId, $uploadDir) : false;
 
    if ($resume || $photo || $aadhar_card || $address_proof) {
        $stmt = $conn->prepare("INSERT INTO applicants (title, first_name, middle_name, last_name, dob, height, weight, gender, marital_status, anniversary_date, spouse_name, permanent_address, permanent_city, permanent_state, permanent_country, permanent_zip,  mobile_number, email, website, instagram, facebook, youtube, local_address, local_city, local_contact_number, father_name, mother_name, family_address, family_city, family_state, family_country, family_zip, emergency_contact_name, emergency_contact_mobile, emergency_contact_relation, emergency_contact_address, 
            position_applied, available_start_date, experience, resume, photo, aadhar_card, address_proof, tenth, twelfth, 
            bachelor, yoga_certification, pan_card, passport, how_heard, university_name
        ) VALUES (?,?,?,?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sssssssssssssssssssssssssssssssssssssssssssssssssss", 
            $_POST['title'], $_POST['first_name'], $_POST['middle_name'], $_POST['last_name'], $_POST['dob'], 
            $_POST['height'], $_POST['weight'], $_POST['gender'], $_POST['marital_status'], $_POST['anniversary_date'],
            $_POST['spouse_name'], $_POST['permanent_address'], $_POST['permanent_city'], $_POST['permanent_state'],
            $_POST['permanent_country'], $_POST['permanent_zip'], $_POST['mobile_number'], $_POST['email'],
            $_POST['website'], $_POST['instagram'], $_POST['facebook'], $_POST['youtube'], $_POST['local_address'],
            $_POST['local_city'], $_POST['local_contact_number'], $_POST['father_name'], $_POST['mother_name'],
            $_POST['family_address'], $_POST['family_city'], $_POST['family_state'], $_POST['family_country'],
            $_POST['family_zip'], $_POST['emergency_contact_name'], $_POST['emergency_contact_mobile'], $_POST['emergency_contact_relation'],
            $_POST['emergency_contact_address'], $_POST['position_applied'], $_POST['available_start_date'], $_POST['experience'],
            $resume, $photo, $aadhar_card, $address_proof, $tenth, $twelfth, $bachelor, $yoga_certification, $pan_card, $passport, implode(", ", $_POST['how_heard']), $_POST['university_name']
        );

    if ($stmt->execute()) {
        echo "<script>
            alert('Data inserted successfully!');
            window.location.href = 'index.php';
        </script>";
        exit;
    } else {
       $errorMessage = $stmt->error; // Capture the error message
    echo "<script>
        alert('Error: $errorMessage');
        window.location.href = 'employee.php'; // Redirect back to the employee page
    </script>";
    exit;
    }

    $stmt->close();
    $conn->close();
}
}
function generateCaptcha() {
    if (rand(0, 1) === 0) {
        $num1 = rand(10, 99);
        $num2 = rand(100, 999);
    } else {
        $num1 = rand(100, 999);
        $num2 = rand(10, 99);
    }
    return "$num1 + $num2 = ?";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Onboarding Form</title>

</head>
<body>
<div class="container mt-5" style="">
    <h2>Employee Onboarding Form</h2>
    <form id="employeeForm" method="POST"enctype="multipart/form-data"action="employee.php">
                <div class="row">
                    <div class="col-md-2">
                        <label for="title"><span style="color:red">*</span>Title:</label>
                        <select class="form-control" id="title" name="title" required>
						 <option value="">Select Title</option>
                            <option value="Mr.">Mr.</option>
                            <option value="Mrs.">Mrs.</option>
                            <option value="Ms.">Ms.</option>
                            <option value="Miss">Miss</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="first_name"><span style="color:red">*</span>First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="col-md-2">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" class="form-control" id="middle_name" name="middle_name">
                    </div>
                    <div class="col-md-4">
                        <label for="last_name"><span style="color:red">*</span>Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                </div>
       <div class="row">
            <div class="col-md-3">
                <label for="dob"><span style="color:red">*</span>Birth Date</label>
                <input type="date" class="form-control" id="dob" name="dob" required>
            </div>
			           <div class="col-md-2">
    <label for="height"><span style="color:red">*</span>Height</label>
    <input type="number" class="form-control" id="height" name="height" placeholder="in cm" min="1" required>
</div>
<div class="col-md-2">
    <label for="weight"><span style="color:red">*</span>Weight</label>
    <input type="number" class="form-control" id="weight" name="weight" placeholder="in Kg" min="1" required>
</div>
            <div class="col-md-2">
                <label for="gender"><span style="color:red">*</span>Gender</label>
                <select class="form-control" id="gender" name="gender" required>
                    <option value="">Select</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
					<option value="Disclose">Not to disclose</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="marital_status"><span style="color:red">*</span>Marital Status</label>
                <select class="form-control" id="marital_status" name="marital_status" required>
                    <option value="">Select</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Divorced">Divorced</option>
					<option value="Other">Not to disclose</option>
                </select>
            </div>
        </div>
        <div class="row" id="spouseDetails" style="display:none;">
            <div class="col-md-4">
                <label for="anniversary_date">Anniversary Date</label>
                <input type="date" class="form-control" id="anniversary_date" name="anniversary_date">
            </div>
            <div class="col-md-4">
                <label for="spouse_name">Spouse Name</label>
                <input type="text" class="form-control" id="spouse_name" name="spouse_name">
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <label for="permanent_address"><span style="color:red">*</span>Permanent Address</label>
                <textarea class="form-control" id="permanent_address" name="permanent_address" required></textarea>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <label for="permanent_city"><span style="color:red">*</span>City</label>
                <input type="text" class="form-control" id="permanent_city" name="permanent_city" required>
            </div>
            <div class="col-md-3">
                <label for="permanent_state"><span style="color:red">*</span>State</label>
                <input type="text" class="form-control" id="permanent_state" name="permanent_state" required>
            </div>
            <div class="col-md-3">
                <label for="permanent_country"><span style="color:red">*</span>Country</label>
                <input type="text" class="form-control" id="permanent_country" name="permanent_country" required>
            </div>
            <div class="col-md-3">
                <label for="permanent_zip"><span style="color:red">*</span>Zip</label>
                <input type="text" class="form-control" id="permanent_zip" name="permanent_zip" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="mobile_number"><span style="color:red">*</span>Mobile Number</label>
                <input type="tel" class="form-control" id="mobile_number" name="mobile_number" pattern="[0-9]{10}" maxlength="10" required>
            </div>
            <div class="col-md-4">
                <label for="email">Email ID</label>
                <input type="email" class="form-control" id="email" name="email">
            </div>
			            <div class="col-md-4">
                <label for="website">Website</label>
                <input type="text" class="form-control" id="website" name="website">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="instagram">Instagram ID</label>
                <input type="text" class="form-control" id="instagram" name="instagram">
            </div>
            <div class="col-md-4">
                <label for="facebook">Facebook ID</label>
                <input type="text" class="form-control" id="facebook" name="facebook">
            </div>
			            <div class="col-md-4">
                <label for="youtube">Youtube ID</label>
                <input type="text" class="form-control" id="youtube" name="youtube">
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <label for="local_address">Local Address</label>
                <textarea class="form-control" id="local_address" name="local_address"></textarea>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="local_city">City</label>
                <input type="text" class="form-control" id="local_city" name="local_city">
            </div>
            <div class="col-md-4">
                <label for="local_contact_number">Local Contact Number</label>
                <input type="text" class="form-control" id="local_contact_number" name="local_contact_number">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <label for="father_name"><span style="color:red">*</span>Father's Name</label>
                <input type="text" class="form-control" id="father_name" name="father_name" required>
            </div>
            <div class="col-md-6">
                <label for="mother_name"><span style="color:red">*</span>Mother's Name</label>
                <input type="text" class="form-control" id="mother_name" name="mother_name" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <label for="family_address"><span style="color:red">*</span>Family Address</label>
                <textarea class="form-control" id="family_address" name="family_address" required></textarea>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <label for="family_city"><span style="color:red">*</span>City</label>
                <input type="text" class="form-control" id="family_city" name="family_city" required>
            </div>
            <div class="col-md-3">
                <label for="family_state"><span style="color:red">*</span>State</label>
                <input type="text" class="form-control" id="family_state" name="family_state" required>
            </div>
            <div class="col-md-3">
                <label for="family_country"><span style="color:red">*</span>Country</label>
                <input type="text" class="form-control" id="family_country" name="family_country" required>
            </div>
            <div class="col-md-3">
                <label for="family_zip"><span style="color:red">*</span>Zip</label>
                <input type="text" class="form-control" id="family_zip" name="family_zip" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <label for="emergency_contact_name"><span style="color:red">*</span>Emergency Contact Name</label>
                <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" required>
            </div>
			<div class="col-md-4">
                <label for="emergency_contact_mobile"><span style="color:red">*</span>Emergency Contact Mobile</label>
                <input type="tel" class="form-control" id="emergency_contact_mobile" name="emergency_contact_mobile" required>
            </div>
			<div class="col-md-4">
                <label for="Relation"><span style="color:red">*</span>Emergency Contact Relation</label>
                <select class="form-control" id="emergency_contact_relation" name="emergency_contact_relation" required>
                    <option value="">Select</option>
					<option value="Spouse">Spouse</option>
                    <option value="Parents">Parents</option>
					<option value="Son / Daughter">Son / Daughter</option>
                    <option value="Friends">Friends</option>
                    <option value="Relative">Relative</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <label for="emergency_contact_address"><span style="color:red">*</span>Emergency Contact Address</label>
                <textarea class="form-control" id="emergency_contact_address" name="emergency_contact_address" required></textarea>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <label for="position_applied"><span style="color:red">*</span>Position Applied</label>
                <select class="form-control" id="position_applied" name="position_applied" required>
				    <option value="">Select your Job Role</option>
                    <option value="Sr. Yoga Trainer">Sr. Yoga Trainer</option>
                    <option value="Jr. Yoga Trainer">Jr. Yoga Trainer</option>
                    <option value="Accountant">Accountant</option>
                    <option value="Marketing Executive">Marketing Executive</option>
                    <option value="Front Office">Front Office</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="available_start_date"><span style="color:red">*</span>Available Start Date</label>
                <input type="date" class="form-control" id="available_start_date" name="available_start_date" required>
            </div>
			<div class="col-md-4">
                <label for="experience"><span style="color:red">*</span>Relevant Experience</label>
                <input type="number" class="form-control" id="experience" name="experience" placeholder="in Years" required>
				<small class="form-text text-muted" id="experience-error" style="color:red; display:none;"></small>
            </div>
			<script>
    document.getElementById('dob').addEventListener('change', function() {
        const dobValue = this.value;
        const experienceInput = document.getElementById('experience');
        const experienceError = document.getElementById('experience-error');

        // Calculate age from DOB
        const dob = new Date(dobValue);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const monthDifference = today.getMonth() - dob.getMonth();

        // Adjust age if the birthday hasn't occurred yet this year
        if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < dob.getDate())) {
            age--;
        }

        // Set the age limit for experience
        experienceInput.setAttribute('max', age);

        // Validate experience against age when the cursor leaves the input field
        experienceInput.addEventListener('blur', function() {
            const experienceValue = parseInt(this.value, 10);
            if (experienceValue >= age) {
				experienceError.textContent = "Experience should be less than your age.";
                experienceError.style.display = "block"; // Show error message
            } else {
                experienceError.style.display = "none"; // Hide error if valid
            }
        });
    });
</script>
        </div>

            <div ><h4>Upload Documents (Mandatory)</h4></div>

        <div class="row">
            <div class="col-md-6">
                <label for="resume"><span style="color:red">*</span>Resume</label>
				<input type="file" class="form-control" id="resume" name="resume" accept=".pdf" REQUIRED>
            </div>
            <div class="col-md-6">
                <label for="photo"><span style="color:red">*</span>Photo</label>
				<input type="file" class="form-control" id="photo" name="photo" accept="image/*" REQUIRED>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <label for="aadhar_card"><span style="color:red">*</span>Aadhar Card</label>
				<input type="file" class="form-control" id="aadhar_card" name="aadhar_card" accept=".pdf" REQUIRED>
            </div>
            <div class="col-md-6">
                <label for="address_proof"><span style="color:red">*</span>Address Proof</label>
				<input type="file" class="form-control" id="address_proof" name="address_proof" accept=".pdf" REQUIRED>
               
            </div>
        </div>
            <div ><h4>Upload Documents (Optional)</h4></div>

      <div class="row">
            <div class="col-md-6">
                <label for="tenth">10th Marksheet</label>
                <input type="file" class="form-control" id="tenth" name="tenth" accept=".pdf">
            </div>
            <div class="col-md-6">
                <label for="twelfth">12th Marksheet</label>
                <input type="file" class="form-control" id="twelfth" name="twelfth" accept=".pdf">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <label for="bachelor">Bachelor Certificate</label>
                <input type="file" class="form-control" id="bachelor" name="bachelor" accept=".pdf">
            </div>
            <div class="col-md-6">
                <label for="yoga_certification">yoga certification</label>
                <input type="file" class="form-control" id="yoga_certification" name="yoga_certification" accept=".pdf">
            </div>
			<div class="col-md-6">
                <label for="pan_card">Pan Card</label>
                <input type="file" class="form-control" id="pan_card" name="pan_card" accept=".pdf">
            </div>
			<div class="col-md-6">
                <label for="passport">passport</label>
                <input type="file" class="form-control" id="passport" name="passport" accept=".pdf">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label><span style="color:red">*</span> How Did You Hear About Us?</label><br>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="linkedin" name="how_heard[]" value="LinkedIn">
                    <label class="form-check-label" for="linkedin">LinkedIn</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="event" name="how_heard[]" value="Event">
                    <label class="form-check-label" for="event">Event</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="social_media" name="how_heard[]" value="Social Media">
                    <label class="form-check-label" for="social_media">Social Media</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="company_website" name="how_heard[]" value="Company Website">
                    <label class="form-check-label" for="company_website">Company Website</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="family_friend" name="how_heard[]" value="Family/Friend">
                    <label class="form-check-label" for="family_friend">Family/Friend</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="university" name="how_heard[]" value="University" onclick="toggleUniversityInput()">
                    <label class="form-check-label" for="university">University</label>
                </div>
            </div>
        </div>
        <div class="row" id="university_input" style="display: none;">
            <div class="col-md-4">
                <label for="university_name">Name of University:</label>
                <input type="text" class="form-control" id="university_name" name="university_name">
            </div>
        </div>

<div class="mb-2">
    <label for="captcha_answer" class="form-label me-2">Write total of: <?= generateCaptcha(); ?></label>
    <input type="text" id="captcha_answer" name="captcha_answer" class="form-control" required>
</div>
  <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
<div>&nbsp;</div></br>
<div>&nbsp;</div>
<script>
window.onload = function() {
    const today = new Date();
    const dd = String(today.getDate()).padStart(2, '0');
    const mm = String(today.getMonth() + 1).padStart(2, '0'); // January is 0!
    const yyyy = today.getFullYear();

    // Format today's date as yyyy-mm-dd for input date attributes
    const todayDate = `${yyyy}-${mm}-${dd}`;

    // Set the maximum date for Anniversary Date (today or before)
    document.getElementById('anniversary_date').setAttribute('max', todayDate);

    // Set the minimum date for Available Start Date (day after today)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tmr_dd = String(tomorrow.getDate()).padStart(2, '0');
    const tmr_mm = String(tomorrow.getMonth() + 1).padStart(2, '0');
    const tmr_yyyy = tomorrow.getFullYear();
    const tomorrowDate = `${tmr_yyyy}-${tmr_mm}-${tmr_dd}`;
    document.getElementById('available_start_date').setAttribute('min', tomorrowDate);

    // Set the maximum date for Anniversary Date (today or before)
    document.getElementById('anniversary_date').setAttribute('max', todayDate); // Maximum Anniversary Date is today

    // Set the minimum date for Anniversary Date (15 years ago from today)
    const minAnniversaryDate = new Date();
    minAnniversaryDate.setFullYear(minAnniversaryDate.getFullYear() - 15);
    const minAnniv_dd = String(minAnniversaryDate.getDate()).padStart(2, '0');
    const minAnniv_mm = String(minAnniversaryDate.getMonth() + 1).padStart(2, '0');
    const minAnniv_yyyy = minAnniversaryDate.getFullYear();
    const minAnnivDate = `${minAnniv_yyyy}-${minAnniv_mm}-${minAnniv_dd}`;

    document.getElementById('anniversary_date').setAttribute('min', minAnnivDate);  // Minimum Anniversary Date is 15 years ago
	 updateGender(); // Check the initial value of the title on page load
            // Attach event listener for change event
            document.getElementById("title").addEventListener("change", updateGender);
};

</script>
 <script>
        // Function to update the gender based on the selected title
        function updateGender() {
            const title = document.getElementById("title").value;
            const genderSelect = document.getElementById("gender");

            // Determine the gender based on the title
            if (title === "Mr.") {
                genderSelect.value = "Male";
            } else if (title === "Mrs." || title === "Miss" || title === "Ms.") {
                genderSelect.value = "Female";
            } else if (title === "Other") {
                genderSelect.value = "Other";
            } else {
                genderSelect.value = ""; // Reset if no title is selected
            }
        }

        // Set gender on page load based on the selected title
        window.onload = function() {
            updateGender(); // Check the initial value of the title on page load
            // Attach event listener for change event
            document.getElementById("title").addEventListener("change", updateGender);
        };
		
		    document.getElementById('marital_status').addEventListener('change', function() {
        var spouseDetails = document.getElementById('spouseDetails');
        if (this.value === 'Married') {
            spouseDetails.style.display = 'flex';
        } else {
            spouseDetails.style.display = 'none';
        }
    });
  function toggleUniversityInput() {
        const universityCheckbox = document.getElementById("university");
        const universityInput = document.getElementById("university_input");
        if (universityCheckbox.checked) {
            universityInput.style.display = "block"; // Show university input
        } else {
            universityInput.style.display = "none"; // Hide university input
            document.getElementById("university_name").value = ""; // Clear input if hidden
        }
    }
   
    </script>
	
	<script>
	    const mobileInput = document.getElementById('mobile_number');
    const experienceError = document.createElement('div'); // Create an error message container
    experienceError.style.color = 'red'; // Style the error message
    mobileInput.parentElement.appendChild(experienceError); // Append it to the parent element of the input

    mobileInput.addEventListener('input', function (e) {
        const value = e.target.value;
        // Remove any non-digit characters and limit to 10 digits
        e.target.value = value.replace(/\D/g, '').slice(0, 10);
    });

    mobileInput.addEventListener('blur', function () {
        const value = this.value;

        // Check if the mobile number is exactly 10 digits and does not start with 0
        if (value.length !== 10 || value.startsWith('0')) {
            experienceError.textContent = "Mobile number should be 10 digits and should not start with 0.";
            experienceError.style.display = "block"; // Show error message
            this.focus(); // Return focus to the mobile number input field
        } else {
            experienceError.style.display = "none"; // Hide error if valid
        }
    });
	
	    const localContactInput = document.getElementById('local_contact_number');
    const localContactError = document.createElement('div'); // Create an error message container
    localContactError.style.color = 'red'; // Style the error message
    localContactInput.parentElement.appendChild(localContactError); // Append it to the parent element of the input

    localContactInput.addEventListener('input', function (e) {
        const value = e.target.value;
        // Remove any non-digit characters and limit to 10 digits
        e.target.value = value.replace(/\D/g, '').slice(0, 10);
    });

    localContactInput.addEventListener('blur', function () {
        const value = this.value;

        // Check if the local contact number is exactly 10 digits and does not start with 0
        if (value.length !== 10 || value.startsWith('0')) {
            localContactError.textContent = "Local contact number should be 10 digits and should not start with 0.";
            localContactError.style.display = "block"; // Show error message
            this.focus(); // Return focus to the local contact number input field
        } else {
            localContactError.style.display = "none"; // Hide error if valid
        }
    });
	
    const emergencyContactInput = document.getElementById('emergency_contact_mobile');
    const emergencyContactError = document.createElement('div'); // Create an error message container
    emergencyContactError.style.color = 'red'; // Style the error message
    emergencyContactInput.parentElement.appendChild(emergencyContactError); // Append it to the parent element of the input

    emergencyContactInput.addEventListener('input', function (e) {
        const value = e.target.value;
        // Remove any non-digit characters and limit to 10 digits
        e.target.value = value.replace(/\D/g, '').slice(0, 10);
    });

    emergencyContactInput.addEventListener('blur', function () {
        const value = this.value;

        // Check if the emergency contact number is exactly 10 digits and does not start with 0
        if (value.length !== 10 || value.startsWith('0')) {
            emergencyContactError.textContent = "Emergency contact number should be 10 digits and should not start with 0.";
            emergencyContactError.style.display = "block"; // Show error message
            this.focus(); // Return focus to the emergency contact mobile input field
        } else {
            emergencyContactError.style.display = "none"; // Hide error if valid
        }
    });		
	document.getElementById('email').addEventListener('blur', function() {
        const email = this.value;
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

        if (!emailPattern.test(email)) {
            alert("Please enter a valid email address.");
            this.focus(); // Keep cursor in the email field
        }
    });
		 document.getElementById('mobile_number').addEventListener('input', function (e) {
        const value = e.target.value;
        // Remove any non-digit characters
        e.target.value = value.replace(/\D/g, '').slice(0, 10);
    });
	 document.getElementById('dob').addEventListener('change', function() {
        const dobValue = this.value;
        const experienceInput = document.getElementById('experience');
        const experienceError = document.getElementById('experience-error');

        // Calculate age from DOB
        const dob = new Date(dobValue);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const monthDifference = today.getMonth() - dob.getMonth();

        // Adjust age if the birthday hasn't occurred yet this year
        if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < dob.getDate())) {
            age--;
        }

        // Set the age limit for experience
        experienceInput.setAttribute('max', age);

        // Validate experience against age when the cursor leaves the input field
        experienceInput.addEventListener('blur', function() {
            const experienceValue = parseInt(this.value, 10);
            if (experienceValue >= age) {
                experienceError.textContent = "Experience should be less than your age.";
                experienceError.style.display = "block"; // Show error message
            } else {
                experienceError.style.display = "none"; // Hide error if valid
            }
        });
    });
	
</script>
    <?php include 'includes/footer.php'; // Include the footer file ?>
</body>
</html>