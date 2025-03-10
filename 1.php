<?php
ob_start();
require_once 'Path.php'; // Include the path file for constants
require_once 'db.php'; // Include database connection
include 'includes/header.php';
require_once 'includes/batch_functions.php'; // Include the function to get batches and studios
$batches_with_studios = getAllBatchesAndStudios();
$batch_data = [];
$clients = [];
$bedCount = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Client Search</title>
    <style>
        #searchDropdown {
            position: absolute;
            z-index: 1000;
            background-color: #fff;
            border: 1px solid #ccc;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            display: none;
        }
        #searchDropdown div {
            padding: 8px;
            cursor: pointer;
        }
        #searchDropdown div:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div style="position: relative; width: 300px;">
        <input type="text" id="searchInput" placeholder="Search clients..." autocomplete="off" class="form-control">
        <div id="searchDropdown"></div>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const searchDropdown = document.getElementById('searchDropdown');

        searchInput.addEventListener('input', function () {
            const query = searchInput.value.trim();
            if (query.length > 0) {
                fetch(`search_clients.php?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        // Clear dropdown
                        searchDropdown.innerHTML = '';
                        if (data.length > 0) {
                            searchDropdown.style.display = 'block';
                            data.forEach(client => {
                                const div = document.createElement('div');
                                div.textContent = `${client.title} ${client.first_name} ${client.middle_name} ${client.last_name}`;
                                div.addEventListener('click', function () {
                                    searchInput.value = div.textContent; // Set the input value
                                    searchDropdown.style.display = 'none'; // Hide dropdown
                                });
                                searchDropdown.appendChild(div);
                            });
                        } else {
                            searchDropdown.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching search results:', error);
                    });
            } else {
                searchDropdown.style.display = 'none';
            }
        });

        // Close dropdown if clicked outside
        document.addEventListener('click', function (event) {
            if (!searchInput.contains(event.target) && !searchDropdown.contains(event.target)) {
                searchDropdown.style.display = 'none';
            }
        });
    </script>
</body>
</html>



<?php
// Handle AJAX search requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['searchQuery'])) {
    $searchSQL = $conn->real_escape_string($_POST['searchQuery']);
    $searchQuery = "SELECT title, first_name, middle_name, last_name 
              FROM clients 
              WHERE title LIKE '%$search%' 
                 OR first_name LIKE '%$search%' 
                 OR middle_name LIKE '%$search%' 
                 OR last_name LIKE '%$search%' 
              LIMIT 10";

    $result = $conn->query($searchQuery);

    $data = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $fullName = trim($row['title'] . " " . $row['first_name'] . " " . $row['middle_name'] . " " . $row['last_name']);
            $data[] = $fullName;
        }
    }
    echo json_encode($data);
    exit; // Prevent further HTML output
}
?>
