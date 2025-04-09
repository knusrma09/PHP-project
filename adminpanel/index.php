<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../config/database.php";

// Get counts for dashboard
$total_patients = 0;
$total_doctors = 0;
$total_appointments = 0;
$total_treatments = 0;

// Count patients
$sql = "SELECT COUNT(*) as total FROM patients";
if($result = $conn->query($sql)){
    if($row = $result->fetch_assoc()){
        $total_patients = $row["total"];
    }
    $result->free();
}

// Count doctors
$sql = "SELECT COUNT(*) as total FROM doctors";
if($result = $conn->query($sql)){
    if($row = $result->fetch_assoc()){
        $total_doctors = $row["total"];
    }
    $result->free();
}

// Count appointments
$sql = "SELECT COUNT(*) as total FROM appointments";
if($result = $conn->query($sql)){
    if($row = $result->fetch_assoc()){
        $total_appointments = $row["total"];
    }
    $result->free();
}

// Count treatments
$sql = "SELECT COUNT(*) as total FROM treatments";
if($result = $conn->query($sql)){
    if($row = $result->fetch_assoc()){
        $total_treatments = $row["total"];
    }
    $result->free();
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Patient Portal</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li class="active"><a href="index.php">Dashboard</a></li>
                    <li><a href="patients.php">Patients</a></li>
                    <li><a href="doctors.php">Doctors</a></li>
                    <li><a href="appointments.php">Appointments</a></li>
                    <li><a href="treatments.php">Treatments</a></li>
                    <li><a href="users.php">Users</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="admin-content">
            <header class="admin-header">
                <h1>Admin Dashboard</h1>
                <div class="admin-user">
                    <span>Welcome, Admin</span>
                </div>
            </header>
            
            <div class="admin-stats">
                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-info">
                        <h3>Total Patients</h3>
                        <p><?php echo $total_patients; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👨‍⚕</div>
                    <div class="stat-info">
                        <h3>Total Doctors</h3>
                        <p><?php echo $total_doctors; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <h3>Appointments</h3>
                        <p><?php echo $total_appointments; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💉</div>
                    <div class="stat-info">
                        <h3>Treatments</h3>
                        <p><?php echo $total_treatments; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="admin-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="add-patient.php" class="btn btn-primary">Add New Patient</a>
                    <a href="add-doctor.php" class="btn btn-primary">Add New Doctor</a>
                    <a href="add-appointment.php" class="btn btn-primary">Schedule Appointment</a>
                    <a href="add-treatment.php" class="btn btn-primary">Record Treatment</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>