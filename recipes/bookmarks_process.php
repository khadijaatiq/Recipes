<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$conn = require __DIR__ . "/config/db.php";

$user_id = $_SESSION['user_id'];
$recipe_id = intval($_GET['recipe_id']);
$source = ($_GET['source'] === 'user') ? 'user' : 'system'; // prevent bad input.

$stmt = $conn->prepare("INSERT IGNORE INTO bookmarks (user_id, recipe_id, recipe_source) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $user_id, $recipe_id, $source);

if ($stmt->execute()) {
    header("Location: dashboard.php?success=bookmarked");
} else {
    header("Location: dashboard.php?error=bookmark_failed");
}

$stmt->close();
?>
