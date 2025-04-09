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

// Delete patient if requested
if(isset($_GET["delete"]) && !empty($_GET["delete"])){
    $sql = "DELETE FROM patients WHERE id = ?";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $_GET["delete"]);
        
        if($stmt->execute()){
            header("location: patients.php?success=1");
            exit();
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        $stmt->close();
    }
}

// Get all patients
$patients = array();
$sql = "SELECT p.*, u.username FROM patients p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.last_name, p.first_name";

if($result = $conn->query($sql)){
    while($row = $result->fetch_assoc()){
        $patients[] = $row;
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
    <title>Manage Patients - Admin Dashboard</title>
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
                    <li><a href="index.php">Dashboard</a></li>
                    <li class="active"><a href="patients.php">Patients</a></li>
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
                <h1>Manage Patients</h1>
                <a href="add-patient.php" class="btn btn-primary">Add New Patient</a>
            </header>
            
            <?php if(isset($_GET["success"]) && $_GET["success"] == 1): ?>
                <div class="alert alert-success">
                    Patient has been deleted successfully.
                </div>
            <?php endif; ?>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Blood Group</th>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($patients)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No patients found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($patients as $patient): ?>
                            <tr>
                                <td><?php echo $patient["id"]; ?></td>
                                <td><?php echo htmlspecialchars($patient["first_name"] . " " . $patient["last_name"]); ?></td>
                                <td><?php echo $patient["age"] ? $patient["age"] : "N/A"; ?></td>
                                <td><?php echo $patient["blood_group"] ? $patient["blood_group"] : "N/A"; ?></td>
                                <td><?php echo $patient["username"] ? htmlspecialchars($patient["username"]) : "N/A"; ?></td>
                                <td class="action-cell">
                                    <a href="view-patient.php?id=<?php echo $patient["id"]; ?>" class="btn btn-sm btn-view">View</a>
                                    <a href="edit-patient.php?id=<?php echo $patient["id"]; ?>" class="btn btn-sm btn-edit">Edit</a>
                                    <a href="patients.php?delete=<?php echo $patient["id"]; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure you want to delete this patient?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>