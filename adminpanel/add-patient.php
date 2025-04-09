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

// Define variables and initialize with empty values
$first_name = $last_name = $age = $weight = $height = $blood_group = $email = $phone = "";
$username = $password = "";
$first_name_err = $last_name_err = $username_err = $password_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate first name
    if(empty(trim($_POST["first_name"]))){
        $first_name_err = "Please enter first name.";
    } else{
        $first_name = trim($_POST["first_name"]);
    }
    
    // Validate last name
    if(empty(trim($_POST["last_name"]))){
        $last_name_err = "Please enter last name.";
    } else{
        $last_name = trim($_POST["last_name"]);
    }
    
    // Validate username if creating account
    if(isset($_POST["create_account"]) && $_POST["create_account"] == 1){
        if(empty(trim($_POST["username"]))){
            $username_err = "Please enter a username.";
        } else{
            // Prepare a select statement
            $sql = "SELECT id FROM users WHERE username = ?";
            
            if($stmt = $conn->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("s", $param_username);
                
                // Set parameters
                $param_username = trim($_POST["username"]);
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    // Store result
                    $stmt->store_result();
                    
                    if($stmt->num_rows == 1){
                        $username_err = "This username is already taken.";
                    } else{
                        $username = trim($_POST["username"]);
                    }
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }

                // Close statement
                $stmt->close();
            }
        }
        
        // Validate password
        if(empty(trim($_POST["password"]))){
            $password_err = "Please enter a password.";     
        } elseif(strlen(trim($_POST["password"])) < 6){
            $password_err = "Password must have at least 6 characters.";
        } else{
            $password = trim($_POST["password"]);
        }
    }
    
    // Get other form data
    $age = !empty($_POST["age"]) ? $_POST["age"] : null;
    $weight = !empty($_POST["weight"]) ? $_POST["weight"] : null;
    $height = !empty($_POST["height"]) ? $_POST["height"] : null;
    $blood_group = !empty($_POST["blood_group"]) ? $_POST["blood_group"] : null;
    $email = !empty($_POST["email"]) ? $_POST["email"] : null;
    $phone = !empty($_POST["phone"]) ? $_POST["phone"] : null;
    
    // Check input errors before inserting in database
    if(empty($first_name_err) && empty($last_name_err) && 
       (empty($username_err) && empty($password_err) || !isset($_POST["create_account"]) || $_POST["create_account"] != 1)){
        
        $user_id = null;
        
        // Create user account if requested
        if(isset($_POST["create_account"]) && $_POST["create_account"] == 1){
            // Prepare an insert statement
            $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
            
            if($stmt = $conn->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("sss", $param_username, $param_password, $param_role);
                
                // Set parameters
                $param_username = $username;
                $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
                $param_role = "patient";
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    $user_id = $conn->insert_id;
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }

                // Close statement
                $stmt->close();
            }
        }
        
        // Insert patient data
        $sql = "INSERT INTO patients (user_id, first_name, last_name, age, weight, height, blood_group, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("issiidsss", $param_user_id, $param_first_name, $param_last_name, $param_age, $param_weight, $param_height, $param_blood_group, $param_email, $param_phone);
            
            // Set parameters
            $param_user_id = $user_id;
            $param_first_name = $first_name;
            $param_last_name = $last_name;
            $param_age = $age;
            $param_weight = $weight;
            $param_height = $height;
            $param_blood_group = $blood_group;
            $param_email = $email;
            $param_phone = $phone;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Redirect to patients page
                header("location: patients.php");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
    
    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Patient - Admin Dashboard</title>
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
                <h1>Add New Patient</h1>
                <a href="patients.php" class="btn btn-primary">Back to Patients</a>
            </header>
            
            <div class="admin-form">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="form-control <?php echo (!empty($first_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $first_name; ?>">
                            <span class="invalid-feedback"><?php echo $first_name_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" class="form-control <?php echo (!empty($last_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $last_name; ?>">
                            <span class="invalid-feedback"><?php echo $last_name_err; ?></span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Age</label>
                            <input type="number" name="age" class="form-control" value="<?php echo $age; ?>">
                        </div>
                        <div class="form-group">
                            <label>Weight (KG)</label>
                            <input type="number" step="0.01" name="weight" class="form-control" value="<?php echo $weight; ?>">
                        </div>
                        <div class="form-group">
                            <label>Height (cm)</label>
                            <input type="number" step="0.01" name="height" class="form-control" value="<?php echo $height; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Blood Group</label>
                            <select name="blood_group" class="form-control">
                                <option value="">Select Blood Group</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $email; ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo $phone; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="create_account" value="1" id="create_account" onchange="toggleAccountFields()"> Create User Account
                        </label>
                    </div>
                    
                    <div id="account_fields" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                <span class="invalid-feedback"><?php echo $username_err; ?></span>
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Add Patient">
                        <a href="patients.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function toggleAccountFields() {
            var accountFields = document.getElementById("account_fields");
            var createAccount = document.getElementById("create_account");
            
            if (createAccount.checked) {
                accountFields.style.display = "block";
            } else {
                accountFields.style.display = "none";
            }
        }
    </script>
</body>
</html>