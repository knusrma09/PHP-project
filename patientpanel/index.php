<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "patient"){
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../config/database.php";

// Get patient information
$patient_id = 0;
$patient_name = "";
$patient_details = array(
    'age' => 'N/A',
    'weight' => 'N/A',
    'height' => 'N/A',
    'blood_group' => 'N/A'
);

$sql = "SELECT p.id, p.first_name, p.last_name, p.age, p.weight, p.height, p.blood_group 
        FROM patients p 
        JOIN users u ON p.user_id = u.id 
        WHERE u.id = ?";

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $_SESSION["id"]);
    
    if($stmt->execute()){
        $result = $stmt->get_result();
        
        if($result->num_rows == 1){
            $row = $result->fetch_assoc();
            $patient_id = $row["id"];
            $patient_name = $row["first_name"] . " " . $row["last_name"];
            
            $patient_details['age'] = $row["age"] ? $row["age"] : 'N/A';
            $patient_details['weight'] = $row["weight"] ? $row["weight"] . " KG" : 'N/A';
            $patient_details['height'] = $row["height"] ? $row["height"] . " cm" : 'N/A';
            $patient_details['blood_group'] = $row["blood_group"] ? $row["blood_group"] : 'N/A';
        }
    }
    $stmt->close();
}

// Get today's appointments
$today_appointment = "No appointment today";
$today = date("Y-m-d");

$sql = "SELECT a.*, d.first_name, d.last_name, d.specialization 
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.id 
        WHERE a.patient_id = ? AND a.appointment_date = ? AND a.status = 'scheduled'";

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("is", $patient_id, $today);
    
    if($stmt->execute()){
        $result = $stmt->get_result();
        
        if($result->num_rows > 0){
            $row = $result->fetch_assoc();
            $today_appointment = "Appointment with " . $row["first_name"] . " " . $row["last_name"] . 
                                " at " . date("h:i A", strtotime($row["appointment_time"]));
        }
    }
    $stmt->close();
}

// Get request statistics
$total_requests = 0;
$active_requests = 0;
$prescriptions_issued = 0;

// Total requests
$sql = "SELECT COUNT(*) as total FROM requests WHERE patient_id = ?";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $patient_id);
    
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()){
            $total_requests = $row["total"];
        }
    }
    $stmt->close();
}

// Active requests
$sql = "SELECT COUNT(*) as active FROM requests WHERE patient_id = ? AND status = 'active'";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $patient_id);
    
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()){
            $active_requests = $row["active"];
        }
    }
    $stmt->close();
}

// Prescriptions issued
$sql = "SELECT COUNT(*) as issued FROM prescriptions WHERE patient_id = ? AND status = 'issued'";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $patient_id);
    
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()){
            $prescriptions_issued = $row["issued"];
        }
    }
    $stmt->close();
}

// Get past treatments
$past_treatments = array();

$sql = "SELECT t.*, d.first_name, d.last_name 
        FROM treatments t 
        JOIN doctors d ON t.doctor_id = d.id 
        WHERE t.patient_id = ? 
        ORDER BY t.treatment_date DESC";

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $patient_id);
    
    if($stmt->execute()){
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()){
            $past_treatments[] = array(
                'date' => $row["treatment_date"],
                'type' => $row["treatment_type"],
                'doctor' => $row["first_name"] . " " . $row["last_name"],
                'notes' => $row["notes"]
            );
        }
    }
    $stmt->close();
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-logo">
                <a href="index.php">
                    <img src="../images/logo.png" alt="Logo">
                </a>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="index.php">
                            <span class="icon">🏠</span>
                        </a>
                    </li>
                    <li>
                        <a href="profile.php">
                            <span class="icon">👤</span>
                        </a>
                    </li>
                    <li>
                        <a href="appointments.php">
                            <span class="icon">📅</span>
                        </a>
                    </li>
                    <li>
                        <a href="records.php">
                            <span class="icon">📋</span>
                        </a>
                    </li>
                    <li>
                        <a href="prescriptions.php">
                            <span class="icon">💊</span>
                        </a>
                    </li>
                    <li>
                        <a href="../logout.php">
                            <span class="icon">🚪</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <header class="dashboard-header">
                <h1>Dashboard</h1>
                <div class="search-container">
                    <input type="text" placeholder="Search" class="search-input">
                    <div class="user-profile">
                        <img src="../images/profile.png" alt="Profile">
                    </div>
                </div>
            </header>
            
            <div class="welcome-section">
                <div class="welcome-icon">
                    <img src="../images/heart-icon.png" alt="Heart Icon">
                </div>
                <h2>Welcome <?php echo htmlspecialchars($patient_name); ?></h2>
            </div>
            
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Patient Details</h3>
                    <div class="card-content">
                        <p>Age: <?php echo htmlspecialchars($patient_details['age']); ?></p>
                        <p>Weight: <?php echo htmlspecialchars($patient_details['weight']); ?></p>
                        <p>Height: <?php echo htmlspecialchars($patient_details['height']); ?></p>
                        <p>Blood: <?php echo htmlspecialchars($patient_details['blood_group']); ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Today</h3>
                    <div class="card-content">
                        <p><?php echo htmlspecialchars($today_appointment); ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Requests</h3>
                    <div class="card-content">
                        <p>Total Requests Raised: <?php echo htmlspecialchars($total_requests); ?></p>
                        <p>Total Active Requests: <?php echo htmlspecialchars($active_requests); ?></p>
                        <p>Prescriptions Issued: <?php echo htmlspecialchars($prescriptions_issued); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="past-treatments">
                <h3>Past Treatments</h3>
                
                <?php if(empty($past_treatments)): ?>
                    <p>No past treatments found.</p>
                <?php else: ?>
                    <?php foreach($past_treatments as $treatment): ?>
                        <div class="treatment-item">
                            <div class="treatment-info">
                                <p class="treatment-date"><?php echo date("j M 'y", strtotime($treatment['date'])); ?></p>
                                <p class="treatment-type">Treatment: <?php echo htmlspecialchars($treatment['type']); ?></p>
                                <p class="treatment-doctor">Doctor: <?php echo htmlspecialchars($treatment['doctor']); ?></p>
                            </div>
                            <div class="treatment-actions">
                                <button class="btn btn-note" onclick="showNotes('<?php echo htmlspecialchars(addslashes($treatment['notes'])); ?>')">Note</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Notes Modal -->
    <div id="notesModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Treatment Notes</h2>
            <div id="notesContent"></div>
        </div>
    </div>
    
    <script>
        // Get the modal
        var modal = document.getElementById("notesModal");
        
        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];
        
        // Function to show notes
        function showNotes(notes) {
            document.getElementById("notesContent").innerText = notes;
            modal.style.display = "block";
        }
        
        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }
        
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>