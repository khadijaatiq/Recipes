<?php
session_start();
require '../config/db.php';

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM Users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            header("Location: ../dashboard.php");
            exit;
        } else {
            // Redirect back with an error
            header("Location: login.php?error=invalidpassword");
            exit;
        }
    } else {
        header("Location: login.php?error=usernotfound");
        exit;
    }
} else {
    // If the form wasn't submitted properly, redirect to login
    header("Location: login.php");
    exit;
}
