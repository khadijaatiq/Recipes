<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require "c:/xampp/htdocs/Recipes-main/config/db.php";

$user_id = $_SESSION['user_id'];

// Unbookmark system recipe
if (
    isset($_POST['unbookmark']) && isset($_POST['recipe_id'])) {
    $recipe_id = intval($_POST['recipe_id']);
    $stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND recipe_id = ?");
    $stmt->bind_param("ii", $user_id, $recipe_id);
    $stmt->execute();
    $stmt->close();
    header("Location: bookmarks.php?success=unbookmarked");
    exit();
}

// Delete user-submitted recipe (only by owner)
if (
    isset($_POST['delete_recipe']) && isset($_POST['recipe_id'])){
    $recipe_id = intval($_POST['recipe_id']);
    // Check ownership
    $check = $conn->prepare("SELECT * FROM recipes WHERE recipe_id = ? AND user_id = ? AND source = 'user'");
    $check->bind_param("ii", $recipe_id, $user_id);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows > 0) {
        // Delete from bookmarks (if exists)
        $del_bm = $conn->prepare("DELETE FROM bookmarks WHERE recipe_id = ?");
        $del_bm->bind_param("i", $recipe_id);
        $del_bm->execute();
        $del_bm->close();
        // Delete the recipe itself
        $del = $conn->prepare("DELETE FROM recipes WHERE RECIPE_ID = ?");
        $del->bind_param("i", $recipe_id);
        $del->execute();
        $del->close();
        header("Location: bookmarks.php?success=deleted");
        exit();
    } else {
        header("Location: bookmarks.php?error=not_owner");
        exit();
    }
}

// (Optional) Add bookmark via GET (legacy logic)
if (isset($_GET['recipe_id'])) {
    $recipe_id = intval($_GET['recipe_id']);
    $stmt = $conn->prepare("INSERT IGNORE INTO bookmarks (user_id, recipe_id) VALUES (?, ?)");
    $stmt->bind_param("iis", $user_id);
    if ($stmt->execute()) {
        header("Location: index.php?success=bookmarked");
    } else {        
        header("Location: index.php?error=bookmark_failed");
    }
    $stmt->close();
    exit();
}

// Fallback
header("Location: bookmarks.php");
?>
