<?php
ob_start();
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';
require_once 'includes/batch_functions.php'; // Include the function to get batches and studios

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$title = ucfirst(strtolower($_POST['title'] ?? ''));
$first_name = ucfirst(strtolower($_POST['first_name'] ?? ''));
$middle_name = ucfirst(strtolower($_POST['middle_name'] ?? ''));
$last_name = ucfirst(strtolower($_POST['last_name'] ?? ''));
$fullPhoneNumber = $_POST['fullPhoneNumber'] ?? ''; // Keep mobile number unchanged
$dob = $_POST['dob'] ?? ''; // Assuming date is kept as is
$gender = ucfirst(strtolower($_POST['gender'] ?? ''));
$mode_of_work = ucfirst(strtolower($_POST['mode_of_work'] ?? ''));
$reason = ucfirst(strtolower($_POST['reason'] ?? ''));
$practiced_yoga = ucfirst(strtolower($_POST['practiced_yoga'] ?? ''));
$where = ucfirst(strtolower($_POST['where'] ?? ''));
$PreferredTime = ucfirst(strtolower($_POST['PreferredTime'] ?? ''));
$PreferredStyle = ucfirst(strtolower($_POST['PreferredStyle'] ?? ''));
$how_long = ucfirst(strtolower($_POST['how_long'] ?? ''));
$any_pain = isset($_POST['any_pain']) ? ucfirst(strtolower(implode(',', $_POST['any_pain']))) : '';
$health_conditions = isset($_POST['health_conditions']) ? ucfirst(strtolower(implode(',', $_POST['health_conditions']))) : '';
$any_medication = ucfirst(strtolower($_POST['any_medication'] ?? ''));
$reference = ucfirst(strtolower($_POST['reference'] ?? ''));
$marketing_approval = isset($_POST['marketing_approval']) ? 1 : 0; // Checkbox

	$CallCode = $_POST['CallCode'];           // Get selected CallCode
    $mobileNumber = $_POST['mobile_number'];  // Get entered mobile number
	$fullPhoneNumber  = $CallCode . $mobileNumber;


    // Handle specific preferred style input
  $specificPreferredStyle = '';
if (strtolower($PreferredStyle) === 'specific') {
    $specificPreferredStyle = ucfirst(strtolower($_POST['PreferredStyleSpecific'] ?? ''));
}

$stmt = $conn->prepare("INSERT INTO enquiry 
    (title, first_name, middle_name, last_name, dob, fullPhoneNumber , gender, mode_of_work, PreferredTime, PreferredStyle, specificPreferredStyle, reason, practiced_yoga, yoga_experience, how_long, any_pain, health_conditions, any_medication, reference, marketing_approval) 
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

$stmt->bind_param("ssssssssssssssissssi", 
    $title,
    $first_name,
    $middle_name,
    $last_name,
    $dob,
    $fullPhoneNumber,
    $gender,
    $mode_of_work,
    $PreferredTime,
    $PreferredStyle,
    $specificPreferredStyle,  // Make sure this is captured and inserted
    $reason,
    $practiced_yoga,
    $yoga_experience, // yoga_experience
    $how_long,
    $any_pain,
    $health_conditions,
    $any_medication,
    $reference,
    $marketing_approval // This is an integer
);

    // Execute the statement
    if ($stmt->execute()) {
        echo "<script>
                alert('Data inserted successfully!');
                window.location.href = 'index.php'; // Redirect to index.php after alert
              </script>";
    } else {
        echo "<script>alert('Error inserting data: " . $stmt->error . "');</script>";
    }

}
// SQL query to fetch ISO, CallCode, CountryName, and PhoneLength from CountryCode table
$sql = "SELECT ISO, CONCAT('+', CallCode) AS CallCode, CountryName, PhoneLength FROM CountryCode";
$result = $conn->query($sql);

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

$conn->close();
ob_end_flush();
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	 <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <title>Welcome to Nisha's Yoga Studio</title>
 <style>
        /* Age text aligned to the right and flashing in red */
        #Age {
            float: right;
            
            animation: flash 1s infinite;
        }

        /* Keyframes for flashing effect */
        @keyframes flash {
            0%, 100% {
                color: Blue;
            }
            50% {
                color: transparent;
            }
        }
    </style></head>
<div class="container mt-5" style="">
	<div class="row mb-3"style="padding-bottom:10px">
		<div class="col-md-2">
			<label for="enquiryType" class="form-label">Enquiring for:</label>
			<select class="form-select" id="enquiryType" name="enquiryType" required>
				<option value="" disabled selected>Select Enquiry</option>
				<option value="Yoga">Yoga</option>
				<option value="Yogic Aharar ">Yogic Aahara </option>
				<option value="Corporate Session">Corporate Session</option>
				<option value="Marketing Appoinment">Marketing Appoinment</option>
			</select>
		</div>
	</div>
	
<form id="EnquiryForm" method="POST" enctype="multipart/form-data" action="" style="display: none;">
<h4 align="right"><span style="color:red">* </span> Marked is compulsory field</h4>
		
	<div class="row"style="padding-bottom:10px">





</div>		
	<div class="row"style="padding-bottom:10px">
               <div class="col-md-2">
    <label for="title"><span style="color:red">* </span>Title:</label>
    <select class="form-control" id="title" name="title" required onchange="updateGender()">
        <option value="" disabled selected>Select a title</option>
        <option value="Mr.">Mr</option>
        <option value="Mrs.">Mrs</option>
        <option value="Ms.">Ms</option>
        <option value="Miss">Miss</option>
        <option value="Other">Other</option>
    </select></div>
                    <div class="col-md-4">
                        <label for="first_name"><span style="color:red">* </span>First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="col-md-2">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" class="form-control" id="middle_name" name="middle_name">
                    </div>
                    <div class="col-md-4">
                        <label for="last_name"><span style="color:red">* </span>Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                </div>
       <div class="row"style="padding-bottom:10px">
	  <div class="col-md-2">
            <label for="CallCode"><span style="color:red">* </span>Country Code</label>
            <select class="form-control" id="CallCode" name="CallCode" required>
                <option value="">Select Country Code</option>
                <?php echo $options; // Populate options here ?>
            </select>
        </div>
        
<!-- Include jQuery and Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    
    $(document).ready(function() {
        // Initialize Select2 for the Country Code dropdown with searching
        $('#CallCode').select2({
            placeholder: "Search for Country Code, Country Name, or ISO",
            allowClear: true,
            width: '100%',
        });

        // Ensure validation also runs when the country code is changed
        $('#CallCode').on('change', function() {
            console.log("Country code changed."); // Debugging statement
            validatePhoneLength(); // Validate when country code changes
        });
    });
</script>

<div class="col-md-3">
            <label for="mobile_number"><span style="color:red">* </span>Mobile Number</label>
            <input type="tel" class="form-control" id="mobile_number" name="mobile_number" pattern="[0-9]*" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
    <small id="phoneError" style="color:red; display:none;">Phone number does not match the required length.</small>
        </div>
		
<script>
    // Function to validate phone number length based on selected country code
    function validatePhoneLength() {
        // Get the selected option from CallCode
        var selectedOption = $('#CallCode option:selected');
        var phoneLength = selectedOption.data('phone-length');  // Get phone length from data attribute
        var mobileNumber = $('#mobile_number').val();           // Get entered mobile number

        // Debugging statements for tracking values
        console.log("Selected phone length: ", phoneLength);
        console.log("Entered mobile number length: ", mobileNumber.length);

        // Compare phone length with mobile number length
        if (phoneLength && mobileNumber.length != phoneLength) {
            $('#phoneError').show();  // Show error if length doesn't match
            return false;
        } else {
            $('#phoneError').hide();  // Hide error if length matches
            return true;
        }
    }

    $(document).ready(function() {
        // Validate phone number length while typing in the mobile number field
        $('#mobile_number').on('input', function() {
            console.log("Typing in mobile number...");
            validatePhoneLength();  // Validate on input
        });

        // Also validate when the country code changes
        $('#CallCode').on('change', function() {
            console.log("Country code changed...");
            validatePhoneLength();  // Validate when country code changes
        });
    });
</script>
           <div class="col-md-3">
                <label for="dob"><span style="color:red">* </span>Birth Date</label> 
                <label id="Age"></label> <!-- Age will be displayed here -->
                <input type="date" class="form-control" id="dob" name="dob" required>
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

        // Add event listener to the date input for 'change' event
        document.getElementById('dob').addEventListener('change', function() {
            var dob = this.value;
            if (dob) {
                var age = calculateAge(dob);
                document.getElementById('Age').innerText = age + " Years";
            } else {
                document.getElementById('Age').innerText = ""; // Clear if no date is selected
            }
        });
    </script>
	
<div class="col-md-3">
    <label for="gender"><span style="color:red">* </span>Gender:</label>
    <select class="form-control" id="gender" name="gender" required>
        <option value="" disabled selected>Select a Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Transgender">Transgender</option>
        <option value="Other">Not to Disclose</option>
    </select>
</div>
	<script>
<script>
    function updateGender() {
        const title = document.getElementById('title').value; // Get selected title
        const genderSelect = document.getElementById('gender'); // Get gender select element

        // Set the gender based on the title
        if (title === "Mr.") {
            genderSelect.value = "Male"; // Set gender to Male
        } else if (title === "Mrs." || title === "Ms." || title === "Miss") {
            genderSelect.value = "Female"; // Set gender to Female
        } else if (title === "Other") {
            genderSelect.value = "Other"; // Set gender to Other
        }
    }
</script>
            </div>
       <div class="row"style="padding-bottom:10px">
	   <div class="col-md-3">
        <label for="mode_of_work"><span style="color:red">* </span>Mode of Work:</label>
        <select name="mode_of_work"class="form-control" required>
		  <option value="" disabled selected>Select Mode of Work</option>
            <option value="Sitting">Sitting</option>
            <option value="Standing">Standing</option>
            <option value="Traveling">Traveling</option>
            <option value="Unscheduled">Unscheduled</option>
            <option value="Mix Mode">Mix Mode</option>
        </select>
</div>
	               <div class="col-md-3">
        <label for="reason"class="form-label"><span style="color:red">* </span>Reason for Joining Yoga:</label>
               <select name="reason"class="form-control" required>
		  <option value="" disabled selected>Select Reason</option>
            <option value="Fitness">Fitness</option>
            <option value="Medical">Medical</option>
            <option value="Leisure">Leisure</option>
            <option value="Career">Career</option>
        </select>
		</div>

		<div class="col-md-3">
			<label for="PreferredTime" class="form-label"><span style="color:red">* </span>Preferred Time:</label>
			<select class="form-select" id="PreferredTime" name="PreferredTime" required>
				<option value="" disabled selected>Select Preferred Time</option>
				<option value="Morning">Morning</option>
				<option value="Afternoon ">Afternoon </option>
				<option value="Evening">Evening</option>
				<option value="Any">Any</option>
			</select>
		</div>
 <div class="col-md-3">
                <label for="PreferredStyle" class="form-label"><span style="color:red">* </span>Preferred Style:</label>
                <select class="form-select" id="PreferredStyle" name="PreferredStyle" required>
                    <option value="" disabled selected>Select Preferred Style</option>
                    <option value="Advance">Advance</option>
                    <option value="Regular">Regular</option>
                    <option value="Tummy Loss">Tummy Loss</option>
                    <option value="Any">Any</option>
					<option value="Specific">Specific</option>
                </select>
            </div>
            <div id="specificStyleContainer" style="display: none;">
    <label for="PreferredStyleSpecific">Please Specify the Preferred Style:</label>
    <input type="text" name="PreferredStyleSpecific" id="PreferredStyleSpecific" value="<?php echo htmlspecialchars($demoData['specificPreferredStyle'] ?? ''); ?>">
</div>

<script>
    // Show or hide specific style input based on Preferred Style selection
    document.getElementById('PreferredStyle').addEventListener('change', function() {
        var specificStyleContainer = document.getElementById('specificStyleContainer');
        if (this.value.toLowerCase() === 'specific') {
            specificStyleContainer.style.display = 'block';
        } else {
            specificStyleContainer.style.display = 'none';
        }
    });
</script>
<div class="row"style="padding-bottom:10px">
    <div class="col-md-12">
        <label for="any_pain" class="form-label"><span style="color:red">* </span>Any Pain:</label>
        <div class="row"style="padding-bottom:10px">
            <div class="col-md-3">
                <input type="checkbox" name="any_pain[]" value="No" id="no_pain">
                <label for="no_pain">No Pain in Body</label>
            </div>
            <div class="col-md-3">
                <input type="checkbox" name="any_pain[]" value="Full Body Pain" id="full_body_pain">
                <label for="full_body_pain">Full Body Pain</label><br>
            </div>
            <div class="col-md-3">
                <input type="checkbox" name="any_pain[]" value="Leg Pain" id="leg_pain">
                <label for="leg_pain">Leg Pain</label><br>
            </div>
            <div class="col-md-3">
                <input type="checkbox" name="any_pain[]" value="Back Pain" id="back_pain">
                <label for="back_pain">Back Pain</label><br>
            </div>
            <div class="col-md-3">
                <input type="checkbox" name="any_pain[]" value="Joint Pain" id="joint_pain">
                <label for="joint_pain">Joint Pain</label><br>
            </div>
            <div class="col-md-3">
                <input type="checkbox" name="any_pain[]" value="Neck Pain" id="neck_pain">
                <label for="neck_pain">Neck Pain</label><br>
            </div>
            <div class="col-md-3">
                <input type="checkbox" name="any_pain[]" value="Shoulder Pain" id="shoulder_pain">
                <label for="shoulder_pain">Shoulder Pain</label><br>
            </div> 
			<div class="col-md-3">
                <input type="checkbox" name="any_pain[]" value="Headache" id="headache">
                <label for="shoulder_pain">Headache</label><br>
            </div>
						<div class="col-md-3">
                <input type="checkbox" name="any_pain[]" value="Chronic Pain" id="chronic_pain">
                <label for="shoulder_pain">Chronic Pain</label><br>
            </div>
            <div class="col-md-3">
                <input type="checkbox" name="any_pain[]" value="Other Pain" id="other_pain" onclick="toggleOtherPainField()">
                <label for="other_pain">Other</label><br>
            </div>
        </div>
        <div class="row" id="other_pain_input" style="display:none;">
            <div class="col-md-3">
                <label for="other_pain_text" class="form-label">Please Specify:</label>
                <input type="text" id="other_pain_text" class="form-control" oninput="updateOtherPainValue()">
            </div>
        </div>
    </div>
</div>
<div class="row"style="padding-bottom:10px">
    <div class="col-md-12">
        <label for="health_conditions" class="form-label"><span style="color:red">* </span>Any Health Conditions:</label><br>
        <div class="row"style="padding-bottom:10px">
            <div class="col-md-3">
                <input type="checkbox" name="health_conditions[]" value="Heart Problems" id="heart_problems">
                <label for="heart_problems">Heart Problems</label><br>
                <input type="checkbox" name="health_conditions[]" value="High Blood Pressure" id="high_blood_pressure">
                <label for="high_blood_pressure">High Blood Pressure</label><br>
                <input type="checkbox" name="health_conditions[]" value="Low Blood Pressure" id="low_blood_pressure">
                <label for="low_blood_pressure">Low Blood Pressure</label><br>
                <input type="checkbox" name="health_conditions[]" value="Back Injury" id="back_injury">
                <label for="back_injury">Injury (Back, Knee)</label><br>
            </div>
            <div class="col-md-3">
                <input type="checkbox" name="health_conditions[]" value="Asthma" id="asthma">
                <label for="asthma">Asthma</label><br>
                <input type="checkbox" name="health_conditions[]" value="Seizures" id="seizures">
                <label for="seizures">Seizures</label><br>
                <input type="checkbox" name="health_conditions[]" value="Diabetes" id="diabetes">
                <label for="diabetes">Diabetes</label><br>
                <input type="checkbox" name="health_conditions[]" value="Thyroid" id="Thyroid">
                <label for="other_condition">Thyroid</label><br>
            </div>
            <div class="col-md-3">
                <input type="checkbox" name="health_conditions[]" value="Any Other Surgery" id="other_surgery">
                <label for="other_surgery">Any Other Surgery</label><br>
                <input type="checkbox" name="health_conditions[]" value="Arthritis" id="arthritis">
                <label for="arthritis">Arthritis</label><br>
                <input type="checkbox" name="health_conditions[]" value="Bone or Joint Problem" id="bone_joint_problem">
                <label for="bone_joint_problem">Bone or Joint Problem</label><br>
                <input type="checkbox" name="health_conditions[]" value="Cancer" id="cancer">
                <label for="bone_joint_problem">Cancer</label><br>
            </div>
            <div class="col-md-3">
                <input type="checkbox" name="health_conditions[]" value="Carpal Tunnel Syndrome" id="carpal_tunnel">
                <label for="carpal_tunnel">Carpal Tunnel Syndrome</label><br>
                <input type="checkbox" name="health_conditions[]" value="Sought Therapy/Counselling" id="therapy_counseling">
                <label for="therapy_counseling">Sought Therapy/Counselling</label><br>
                <input type="checkbox" name="health_conditions[]" value="Pregnant" id="pregnant">
                <label for="pregnant">Pregnant</label><br>
				<input type="checkbox" name="health_conditions[]" value="Other" id="other_condition" onclick="toggleOtherConditionField()">
                <label for="other_condition">Other</label><br>
            </div>
        </div>
        <div class="row" id="other_condition_input" style="display:none;">
            <div class="col-md-4">
                <label for="other_condition_text" class="form-label">Please Specify:</label>
                <input type="text" id="other_condition_text" class="form-control" oninput="updateOtherConditionValue()">
            </div>
        </div>
    </div>
</div>

       <div class="row"style="padding-bottom:10px">

            <div class="col-md-12">
        <label for="any_medication"class="form-label"><span style="color:red">* </span>Any Medication:</label>
        <textarea name="any_medication"class="form-control"></textarea>
</div></div>
       <div class="row"style="padding-bottom:10px">

           
	  </div>
	  <div class="row" style="padding-bottom:10px">
	   <div class="col-md-3">
        <label for="reference"class="form-label">Reference:</label>
        <select name="reference"class="form-control">
		<option value="" disabled selected>Select Reference</option>
		<option value="Reference">Reference</option>
            <option value="Event">Event</option>
            <option value="Social Media">Social Media</option>
            <option value="Print Media">Print Media</option>
            <option value="Others">Others</option>
        </select>
      </div> 
	  <div class="col-md-3">
        <label class="form-label"class="form-label"><span style="color:red">* </span>Have you ever practised Yoga?</label></br>
        <input type="radio" name="practiced_yoga" value="Yes" id="yoga_yes" required> Yes
        <input type="radio" name="practiced_yoga" value="No" id="yoga_no" > No
    </div>
	  
    <div class="col-md-4" id="yoga_experience" style="display:none;">
        <label for="yoga_experience" class="form-label">Where?</label>
        <input type="text" name="yoga_experience" class="form-control" placeholder="Name of Institute">
    </div>
    
    <div class="col-md-2" id="yoga_experience1" style="display:none;">
        <label for="how_long" class="form-label">How Long?</label>
        <input type="number" name="how_long" class="form-control" placeholder="in Years">
    </div>


</div>
	  <div class="row" style="padding-bottom:10px">
  <div class="col-md-6">
    <label>
      <input type="checkbox" name="marketing_approval">&nbsp;By ticking, you approve us to send our Marketing / Offers Message.</label>
  </div>
</div>

<button id="submit-btn" class="btn btn-primary">Submit</button>

</form>
<div>&nbsp;</div>
<div>&nbsp;</div>
</div>
<script>
document.getElementById('enquiryType').addEventListener('change', function() {
    var selectedValue = this.value;
    var enquiryForm = document.getElementById('EnquiryForm');

    // Show the form if "Yoga" is selected, otherwise hide it
    if (selectedValue === 'Yoga') {
        enquiryForm.style.display = 'block'; // Show the enquiry form
    } else {
        enquiryForm.style.display = 'none'; // Hide the enquiry form
    }
});

// Get the radio buttons and experience fields
var yogaYes = document.getElementById('yoga_yes');
var yogaNo = document.getElementById('yoga_no');
var yogaExperience = document.getElementById('yoga_experience');
var yogaExperience1 = document.getElementById('yoga_experience1');

// Initially hide the yoga experience fields
yogaExperience.style.display = 'none';
yogaExperience1.style.display = 'none';

// Add event listeners to both radio buttons
yogaYes.addEventListener('change', function() {
    if (yogaYes.checked) {
        yogaExperience.style.display = 'block'; // Show the "Where?" field
        yogaExperience1.style.display = 'block'; // Show the "How Long?" field
    }
});

// Hide experience fields when "No" is selected
yogaNo.addEventListener('change', function() {
    if (yogaNo.checked) {
        yogaExperience.style.display = 'none'; // Hide the "Where?" field
        yogaExperience1.style.display = 'none'; // Hide the "How Long?" field
    }
});
    yogaNo.addEventListener('change', function() {
        if (yogaNo.checked) {
            yogaExperience.style.display = 'none'; // Hide the "Where?" field
            yogaExperience1.style.display = 'none'; // Hide the "How Long?" field
        }
    });
</script>

<script>
       function toggleOtherPainField() {
        const otherPainInput = document.getElementById('other_pain_input');
        const otherPainCheckbox = document.getElementById('other_pain');
        
        // Toggle the visibility of the "Please Specify" input field
        if (otherPainCheckbox.checked) {
            otherPainInput.style.display = 'block';
        } else {
            otherPainInput.style.display = 'none';
            document.getElementById('other_pain_text').value = ''; // Clear input when unchecking
        }
    }

    function updateOtherPainValue() {
        const otherPainText = document.getElementById('other_pain_text').value;
        const otherPainCheckbox = document.getElementById('other_pain');
        
        // Update the "Other" checkbox value with the input text
        otherPainCheckbox.value = otherPainText ? otherPainText : 'Other Pain';
    }
   
   
	function toggleOtherConditionField() {
        const otherConditionInput = document.getElementById('other_condition_input');
        const otherConditionCheckbox = document.getElementById('other_condition');
        
        // Toggle the visibility of the "Please Specify" input field
        if (otherConditionCheckbox.checked) {
            otherConditionInput.style.display = 'block';
        } else {
            otherConditionInput.style.display = 'none';
            document.getElementById('other_condition_text').value = ''; // Clear input when unchecking
        }
    }

    function updateOtherConditionValue() {
        const otherConditionText = document.getElementById('other_condition_text').value;
        const otherConditionCheckbox = document.getElementById('other_condition');
        
        // Update the "Other" checkbox value with the input text
        otherConditionCheckbox.value = otherConditionText ? otherConditionText : 'Other';
    }
	function updateGender() {
        var title = document.getElementById('title').value;
        var genderSelect = document.getElementById('gender');

        // Reset gender selection
        genderSelect.selectedIndex = 0; // Set to "Select a Gender"

        // Update gender based on title
        if (title === "Mr.") {
            genderSelect.value = "Male"; // Set gender to Male
        } else if (title === "Other") {
            genderSelect.value = "Disclose"; // Set gender to Disclose
        } else if (["Mrs.", "Ms.", "Miss"].includes(title)) {
            genderSelect.value = "Female"; // Set gender to Female
        }
    }
</script>
<script>
    // Function to capitalize the first letter of the input value
    function capitalizeFirstLetter(input) {
        const value = input.value;
        if (value.length > 0) {
            input.value = value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
        }
    }

    // Add event listeners to relevant fields
    document.getElementById('title').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('first_name').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('middle_name').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('last_name').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('mobile_number').addEventListener('input', function() {
        // You can keep the mobile number as is or handle it separately
    });
    document.getElementById('dob').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('mode_of_work').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('PreferredTime').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('PreferredStyle').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('reason').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('where').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('how_long').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('any_medication').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('reference').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('health_conditions').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
    document.getElementById('any_pain').addEventListener('input', function() {
        capitalizeFirstLetter(this);
    });
</script>


<?php include 'includes/footer.php'; // Include the footer file ?>
</body>
</html>
