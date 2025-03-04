<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start(); // Start output buffering
require '../config/db.php';

if (isset($_POST['submit'])) {
    echo "Form submitted successfully.<br>"; 

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($password !== $confirmPassword) {
        die("Error"); 
        //header("Location: register.php?error=password_mismatch");
        //exit();
    }

    // Check if username or email already exists
    $sql = "SELECT * FROM Users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        die("Error");
       // header("Location: register.php?error=username_taken");
        //exit();
    }

    // Hash password before storing
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO Users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $hashedPassword);
    $stmt->execute();

    // Ensure no output before redirect
    if (headers_sent()) {
        echo "<script>window.location.href = '../auth/login.php?success=registered';</script>";
    } else {
        header("Location: ../auth/login.php?success=registered");
        exit();
    }
}
?>
