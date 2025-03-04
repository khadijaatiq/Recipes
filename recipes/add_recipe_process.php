<?php
require '../config/db.php';

if (isset($_POST['submit'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];

    $sql = "INSERT INTO Recipes (title, description, category_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $title, $description, $category_id);
    $stmt->execute();

    echo "Recipe added successfully!";
}
?>
