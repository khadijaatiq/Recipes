<?php
session_start();
require "c:/xampp/htdocs/Recipes-main/config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$recipe_id = isset($_POST['recipe_id']) ? (int)$_POST['recipe_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $recipe_id > 0) {
    $conn->begin_transaction();
    try {
        $check = $conn->prepare("SELECT RECIPE_ID FROM recipes WHERE RECIPE_ID = ? AND user_id = ? AND source = 'user'");
        $check->bind_param("ii", $recipe_id, $user_id);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $delBookmarks = $conn->prepare("DELETE FROM bookmarks WHERE recipe_id = ?");
            $delBookmarks->bind_param("i", $recipe_id);
            $delBookmarks->execute();
            
            $delete = $conn->prepare("DELETE FROM recipes WHERE RECIPE_ID = ?");
            $delete->bind_param("i", $recipe_id);
            
            if ($delete->execute()) {
                $conn->commit();
                header("Location: bookmarks.php?success=Recipe+deleted");
                exit();
            }
        }
        
        $conn->rollback();
        header("Location: bookmarks.php?error=Delete+failed");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: bookmarks.php?error=Delete+error");
        exit();
    }
}

header("Location: bookmarks.php");
?>
