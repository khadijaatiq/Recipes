<?php
session_start();

include '../ProjectDB/partials/header.php'; 
include 'c:/xampp/htdocs/ProjectDB/config/db.php';

$recipe_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($recipe_id === 0) {
    echo "<p>Recipe not found.</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        echo "<script>alert('You must be logged in to submit a review.');</script>";
    } elseif ($rating >= 1 && $rating <= 5) {
        if(!empty($comment))
        {
            $insert_stmt = $conn->prepare("INSERT INTO ratings (user_id, recipe_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $insert_stmt->bind_param("iiis", $user_id, $recipe_id, $rating, $comment);
        }
        else{
            $insert_stmt = $conn->prepare("INSERT INTO ratings (user_id, recipe_id, rating, created_at) VALUES (?, ?, ?, NOW())");
        $insert_stmt->bind_param("iii", $user_id, $recipe_id, $rating);
        }
        if ($insert_stmt->execute()) {
            echo "<script>alert('Review submitted successfully!'); window.location.href = window.location.href;</script>";
            exit;
        } else {
            echo "<p>Error submitting review. Please try again later.</p>";
        }
    } else {
        echo "<p>Invalid rating or comment.</p>";
    }
}

// Fetch recipe
$recipe_stmt = $conn->prepare("SELECT * FROM recipes WHERE RECIPE_ID = ?");
$recipe_stmt->bind_param("i", $recipe_id);
$recipe_stmt->execute();
$recipe_result = $recipe_stmt->get_result();
$recipe = $recipe_result->fetch_assoc();

if (!$recipe) {
    echo "<p>Recipe not found.</p>";
    exit;
}

// Fetch ingredients
$ingredients_stmt = $conn->prepare("SELECT i.name, ri.quantity FROM recipe_ingredients ri JOIN ingredients i ON ri.ingredient_id = i.ingredient_id WHERE ri.recipe_id = ?");
$ingredients_stmt->bind_param("i", $recipe_id);
$ingredients_stmt->execute();
$ingredients_result = $ingredients_stmt->get_result();

// Fetch instructions
$instructions_stmt = $conn->prepare("SELECT step_number, description FROM instructions WHERE recipe_id = ? ORDER BY step_number");
$instructions_stmt->bind_param("i", $recipe_id);
$instructions_stmt->execute();
$instructions_result = $instructions_stmt->get_result();

// Fetch ratings
$ratings_stmt = $conn->prepare("SELECT u.username, r.rating, r.comment, r.created_at FROM ratings r JOIN users u ON u.user_id = r.user_id WHERE recipe_id = ? ORDER BY created_at DESC");
$ratings_stmt->bind_param("i", $recipe_id);
$ratings_stmt->execute();
$ratings_result = $ratings_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($recipe['NAME']) ?></title>
</head>
<body>
<div class="main-content">
    <div class="recipe-detail">
        <div class="recipe-header">
            <h1><?= htmlspecialchars($recipe['NAME']) ?></h1>
        </div>
        <div class="recipe-description">
            <?php if (!empty($recipe['DESCRIPTION'])): ?>
                <p class="recipe-description"><?= htmlspecialchars($recipe['DESCRIPTION'])?></p>
            <?php endif; ?>
        </div>
        <div class="recipe-image">   
            <img src="<?= htmlspecialchars($recipe['image_url']) ?: 'default.jpg' ?>" alt="<?= htmlspecialchars($recipe['NAME']) ?>">
        </div>
        <div class="recipe-meta-box" style="border: 1px solid #ccc; padding: 15px; margin-top: 20px; border-radius: 8px; background-color:rgb(255, 252, 252); display: flex; gap: 20px; flex-wrap: wrap; max-width: 100%;">
            <p><strong>Cuisine:</strong> <?= htmlspecialchars($recipe['CUISINE']) ?></p>
            <p><strong>Meal Type:</strong> Lunch </p>
            <p><strong>Dietary Restrictions:</strong> Vegetarian</p>
            <p><strong>Total Cooking Time:</strong> <?= htmlspecialchars($recipe['COOKING_TIME']) ?> minutes</p>
        </div>
        <div class="recipe-ingredients">
            <h2>Ingredients</h2>
            <ul>
                <?php while ($ingredient = $ingredients_result->fetch_assoc()): ?>
                    <li><?= htmlspecialchars($ingredient['quantity']) ?> - <?= htmlspecialchars($ingredient['name']) ?></li>
                <?php endwhile; ?>
            </ul>
        </div>

        <div class="recipe-instructions">
            <h2>Instructions</h2>
            <ol>
                <?php while ($step = $instructions_result->fetch_assoc()): ?>
                    <li><?= htmlspecialchars($step['description']) ?></li>
                <?php endwhile; ?>
            </ol>
        </div>

        <div class="review">
            <div class="review-header">
                <h2>Ratings & Reviews</h2>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <h3>Submit Your Review</h3>
                <form method="post" action="">
                    <label for="rating">Rating* (1–5):</label>
                    <select name="rating" id="rating" required>
                        <option value="">Select</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select><br><br>

                    <label for="comment">Comment:</label><br>
                    <textarea name="comment" id="comment" rows="4" cols="50" ></textarea><br><br>
                    <input type="submit" class="button button-primary" name="submit_review" value="Submit Review">
                </form>
            <?php else: ?>
                <p><strong>You must be <a href="/login.php">logged in</a> to submit a review.</strong></p>
            <?php endif; ?>

            <h3>User Reviews</h3>
            <?php if ($ratings_result->num_rows > 0): ?>
                <?php while ($review = $ratings_result->fetch_assoc()): ?>
                    <div class="rating-box">
                        <strong>Rating: <?= htmlspecialchars($review['rating']) ?>/5</strong>
                        <p class="review-author"><?= htmlspecialchars($review['username'])?></p>
                        <p class="review-comment"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                        <small class="review-date">Posted on <?= date('j/n/Y', strtotime($review['created_at'])) ?></small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No reviews yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
