<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$mysqli = require __DIR__ . "../config/db.php";

$user_id = $_SESSION['user_id'];
$userQuery = "SELECT * FROM users WHERE user_id = ?";
$stmt = $mysqli->prepare($userQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$recipeQuery = "SELECT * FROM user_submitted_recipes WHERE user_id = ? union SELECT * FROM bookmarks WHERE user_id = ?";
$stmt = $mysqli->prepare($recipeQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recipes = $stmt->get_result();
$stmt->close();
?>
 <?php 
 include 'partials/header.php'; 
 include 'partials/navbar.php'; 
?>
    <div class="container">
        <div class="search-bar">
            <input type="text" placeholder="Search for recipes...">
        </div>

        <section class="popular-recipes">
            <h2>Popular Recipes</h2>
            <div class="recipe-list">
                <div class="recipe-card">
                    <img src="placeholder.jpg" alt="Recipe Image">
                    <h3>Name</h3>
                    <p>★ 4.5 (3)</p>
                </div>
                <div class="recipe-card">
                    <img src="placeholder.jpg" alt="Recipe Image">
                    <h3>Name</h3>
                    <p>★ 4.5 (3)</p>
                </div>
                <div class="recipe-card">
                    <img src="placeholder.jpg" alt="Recipe Image">
                    <h3>Name</h3>
                    <p>★ 4.5 (3)</p>
                </div>
                <div class="recipe-card">
                    <img src="placeholder.jpg" alt="Recipe Image">
                    <h3>Name</h3>
                    <p>★ 4.5 (3)</p>
                </div>
            </div>
        </section>

        <section class="ingredient-search">
            <h2>Add Ingredients You Have at Home</h2>
            <p>Get curated recipes</p>
            <button>Try It Out</button>
        </section>
    </div>
<?php include 'partials/footer.php'; ?>
