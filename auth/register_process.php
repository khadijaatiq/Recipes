<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

if (!file_exists('../config/db.php')) {
    die("Database file not found!");
}

require_once '../config/db.php';

if (!file_exists('../auth/database.php')) {
    die("Database connection file not found!");
}

require_once '../auth/database.php';

if ($conn->connect_errno) {
    die("Database connection failed: " . $conn->connect_error);
}

// Processing form data after POST submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Check if the passwords match
    if ($password !== $confirmPassword) {
        header("Location: ../auth/register.php?error=password_mismatch");
        exit;
    }

    // Check if username or email already exists
    $sql = "SELECT * FROM Users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $email);
    
    if (!$stmt->execute()) {
        die("Error executing query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        header("Location: ../auth/register.php?error=username_taken");
        exit;
    }

    // Hash password before storing
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO Users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $hashedPassword);

    if (!$stmt->execute()) {
        die("Error inserting user: " . $stmt->error);
    }

    // Redirect to login page with success message
    header("Location: ../auth/login.php?success=registered");
    exit();
}

ob_end_flush(); // Flush output buffer
?>
