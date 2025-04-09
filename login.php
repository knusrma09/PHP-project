<?php
session_start();

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
    exit;
}

require_once "config/database.php";

$username = $password = "";
$username_err = $password_err = $login_err = "";
$signup_success = "";

// Login Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {

    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    if (empty($username_err) && empty($password_err)) {
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username, $hashed_password, $role);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;

                            if ($role == "admin") {
                                header("location: admin/index.php");
                            } else if ($role == "doctor") {
                                header("location: doctor/index.php");
                            } else {
                                header("location: patient/index.php");
                            }
                        } else {
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid username or password.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
}

// Signup Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    $new_username = trim($_POST["new_username"]);
    $new_password = password_hash(trim($_POST["new_password"]), PASSWORD_DEFAULT);
    $role = "patient";

    $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sss", $new_username, $new_password, $role);
        if ($stmt->execute()) {
            $signup_success = "Signup successful! You can now login.";
        } else {
            $signup_success = "Error during signup. Username might already exist.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Patient Portal</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .login-container { width: 400px; margin: auto; padding: 30px; }
        .btn { margin-top: 10px; }
        .form-group { margin-bottom: 15px; }
        .alert { padding: 10px; margin-bottom: 15px; }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Patient Portal Login</h2>

        <?php 
        if (!empty($login_err)) {
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }    
        if (!empty($signup_success)) {
            echo '<div class="alert alert-success">' . $signup_success . '</div>';
        }    
        ?>

        <!-- Login Form -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="login" value="1">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>" required>
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
        </form>

        <!-- Signup Form -->
        <form method="post" action="">
            <input type="hidden" name="signup" value="1">
            <h3>Don't have an account?</h3>
            <div class="form-group">
                <label>New Username</label>
                <input type="text" name="new_username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-secondary" value="Sign Up">
            </div>
        </form>

        <!-- Forgot Password Button -->
        <div>
            <a href="forgot_password.php" class="btn btn-link">Forgot Password?</a>
        </div>
    </div>
</body>
</html>
