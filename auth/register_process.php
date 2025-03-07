<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start(); // Start output buffering
echo "Step 0: Starting registration process...\n";
// Ensure db.php exists
if (!file_exists('../config/db.php')) {
    die("Database file not found!");
}
echo "Step 0.1: Required files found.<br>\n";
require_once '../config/db.php';
echo "Step 0.2: Required files included.<br>\n";
// Ensure database.php exists
if (!file_exists('../auth/database.php')) {
    die("Database connection file not found!");
}
echo "Step 0.3: Required files found.<br>\n";
require_once '../auth/database.php';
echo "Step 0.4: Required files included.<br>\n";
// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
echo "Step 0.5: Database connected successfully.<br>\n";
// Check if form is submitted
print_r($_POST);
if (isset($_POST['submit'])) {
    echo "Step 1: Form submitted.<br>\n"; 

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Debugging output
    echo "Step 2: Username: $username, Email: $email <br>";

    if ($password !== $confirmPassword) {
        die("Error: Passwords do not match.");
    }

    // Check if username or email already exists
    $sql = "SELECT * FROM Users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $email);
    
    if (!$stmt->execute()) {
        die("Error executing query: " . $stmt->error);
    }
    echo "Step 2.1: Query executed successfully.<br>";
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        die("Error: Username or Email already exists.");
    }
    echo "Step 2.2: Username and Email are unique.<br>";
    // Hash password before storing
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO Users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $hashedPassword);
    echo "Step 2.3: User data prepared.<br>";
    if (!$stmt->execute()) {
        die("Error inserting user: " . $stmt->error);
    } else {
        echo "Step 3: User registered successfully!";
    }
    echo "Step 4: Redirecting to login page...<br>";
    // Redirect to login page
    if (headers_sent()) {
        echo "<script>window.location.href = '../auth/login.php?success=registered';</script>";
    } else {
        header("Location: ../auth/login.php?success=registered");
    }
    echo "Step 5: Registration process completed.<br>";
    exit();
}
echo "Step 6: Form not submitted.<br>";
ob_end_flush(); // Flush output buffer
?>
