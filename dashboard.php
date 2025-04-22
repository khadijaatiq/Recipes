<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$mysqli = require __DIR__ . "/config/db.php";
//add columns to database for user submitted recipes
//add columns to database for bookmarks
if($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}   
$user_id = $_SESSION['user_id'];
$userQuery = "SELECT * FROM users WHERE user_id = ?";
$stmt = $mysqli->prepare($userQuery);
if(!$stmt) {
    die("Prepare failed: " . $mysqli->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$recipeQuery = "SELECT recipe_id FROM user_submitted_recipes WHERE user_id = ? union SELECT recipe_id FROM bookmarks WHERE user_id = ?";
$stmt = $mysqli->prepare($recipeQuery);
if(!$stmt) {
    die("Prepare failed: " . $mysqli->error);
}
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$recipes = $stmt->get_result();
$stmt->close();
?>
 <?php 
 include 'partials/header.php'; ?>
    <div class="container">
        <div class="search-bar">
            <input type="text" placeholder="Search for recipes...">
        </div>
        <section class="popular-recipes">
            <h2>Popular Recipes</h2>
            <div class="recipe-list">
            <?php 
                if ($recipes->num_rows > 0) {
                    while ($recipe = $recipes->fetch_assoc()) {
                        // Fetch more details about each recipe, assuming you have a 'recipes' table
                        $recipeDetailsQuery = "SELECT recipe_name, rating, reviews FROM recipes WHERE recipe_id = ?";
                        $stmt = $mysqli->prepare($recipeDetailsQuery);
                        if (!$stmt) {
                            die("Failed to prepare recipe details query: " . $mysqli->error);
                        }
                        $stmt->bind_param("i", $recipe['recipe_id']);
                        if (!$stmt->execute()) {
                            die("Failed to execute recipe details query: " . $stmt->error);
                        }
                        $recipeDetails = $stmt->get_result()->fetch_assoc();
                        $stmt->close();

                        echo '<div class="recipe-card">';
                        echo '<img src="placeholder.jpg" alt="Recipe Image">';
                        echo '<h3>' . htmlspecialchars($recipeDetails['recipe_name']) . '</h3>';
                        echo '<p>★ ' . htmlspecialchars($recipeDetails['rating']) . ' (' . htmlspecialchars($recipeDetails['reviews']) . ')</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No recipes found.</p>';
                }
                ?>
            </div>
        </section>

        <section class="ingredient-search">
            <h2>Add Ingredients You Have at Home</h2>
            <p>Get curated recipes</p>
            <button>Try It Out</button>
        </section>
    </div>
<?php include 'partials/footer.php'; ?>
