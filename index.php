<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the user is a student
    $sql = "SELECT * FROM students WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($password == $user['password']) {  // Compare plain text password
            // Check portal status before allowing login
            if ($user['portal_status'] == 'inactive') {
                echo "<script>alert('Your account is inactive. Please contact admin for access.'); window.location.href='index.php';</script>";
                exit();
            }
            $_SESSION['student_id'] = $user['id'];
            header("Location: student_dashboard.php");
            exit();
        } else {
            echo "<script>alert('Invalid password.'); window.location.href='index.php';</script>";
            exit();
        }
    } else {
        // Check if the user is an admin
        $sql = "SELECT * FROM admin WHERE username='$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if ($password == $admin['password']) {  // Compare plain text password
                $_SESSION['id'] = $admin['id'];
                header("Location: admin_dashboard.php");  // Redirect to admin dashboard
                exit();
            } else {
                echo "<script>alert('Invalid password.'); window.location.href='index.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('User not found.'); window.location.href='index.php';</script>";
            exit();
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="css/css.css">
</head>
<body style="background-image: url('img/1010.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <div class="login-container">
        <h2>Student Management System</h2>
        <form method="post">
            <div class="input-group">
                <input type="text" name="email" placeholder="Email / Username" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit">Log In</button>
            <div class="or-divider">
                <span>OR</span>
            </div>
            <a href="#" class="forgot-password">Forgot password?</a>
        </form>
        <div class="signup-link">
            <!-- <a href="#">Sign up</a> -->
        </div>
    </div>
</body>
</html>
