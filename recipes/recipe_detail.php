<?php
session_start();
include 'c:/xampp/htdocs/Recipes-main/partials/header.php';
include 'c:/xampp/htdocs/Recipes-main/config/db.php';

$recipe_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$source = isset($_GET['source']) ? ($_GET['source'] === 'user' ? 'user' : 'system') : 'system';

// Bookmark handling
if (isset($_POST['toggle_bookmark'])) {
    if (!isset($_SESSION['user_id'])) {
        die();
    }

    $userId = $_SESSION['user_id'];
    $recipeId = (int)$_POST['recipe_id'];
    $action = $_POST['action'];

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO bookmarks (user_id, recipe_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $recipeId);
        if ($stmt->execute()) {
            $logStmt = $conn->prepare("INSERT INTO bookmark_logs (user_id, recipe_id) VALUES (?, ?)");
            $logStmt->bind_param("ii", $userId, $recipeId);
            $logStmt->execute();
            $logStmt->close();
        }
    } else {
        $stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND recipe_id = ?");
        $stmt->bind_param("ii", $userId, $recipeId);
        $stmt->execute();
    }
    $stmt->close();

    header("Location: ".$_SERVER['PHP_SELF']."?id=".$recipeId);
    exit;
}

// Fetch user's bookmarks if logged in
$bookmarkedRecipes = [];
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $bookmarkStmt = $conn->prepare("SELECT recipe_id FROM bookmarks WHERE user_id = ?");
    $bookmarkStmt->bind_param("i", $userId);
    $bookmarkStmt->execute();
    $bookmarkResult = $bookmarkStmt->get_result();
    while ($bookmark = $bookmarkResult->fetch_assoc()) {
        $bookmarkedRecipes[$bookmark['recipe_id']] = true;
    }
    $bookmarkStmt->close();
}

if ($recipe_id === 0) {
    echo "<p>Recipe not found.</p>";
    exit;
}

// Check if recipe is private
$is_private = false;
$privacy_stmt = $conn->prepare("SELECT status FROM recipes WHERE RECIPE_ID = ?");
$privacy_stmt->bind_param("i", $recipe_id);
$privacy_stmt->execute();
$privacy_result = $privacy_stmt->get_result();
if ($privacy_row = $privacy_result->fetch_assoc()) {
    $is_private = ($privacy_row['status'] === 'private');
}
$privacy_stmt->close();

// Review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        echo "<script>alert('You must be logged in to submit a review.');</script>";
    }
    elseif ($rating >= 1 && $rating <= 5) {
        if(!empty($comment)) {
            $insert_stmt = $conn->prepare("INSERT INTO ratings (user_id, recipe_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
            $insert_stmt->bind_param("iiis", $user_id, $recipe_id, $rating, $comment);
        } else {
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
$recipe_stmt = $conn->prepare("SELECT r.*, u.username 
    FROM recipes r
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.RECIPE_ID = ?");
$recipe_stmt->bind_param("i", $recipe_id);
$recipe_stmt->execute();
$recipe_result = $recipe_stmt->get_result();
$recipe = $recipe_result->fetch_assoc();

if (!$recipe) {
    echo "<p>Recipe not found.</p>";
    exit;
}

// Fetch cuisines
$cuisines_stmt = $conn->prepare("
    SELECT c.name 
    FROM recipe_cuisines rc 
    JOIN cuisines c ON rc.cuisine_id = c.cuisine_id 
    WHERE rc.recipe_id = ?
");
$cuisines_stmt->bind_param("i", $recipe_id);
$cuisines_stmt->execute();
$cuisines_result = $cuisines_stmt->get_result();
$cuisines = [];
while ($cuisine = $cuisines_result->fetch_assoc()) {
    $cuisines[] = $cuisine['name'];
}
$cuisines_stmt->close();

// Fetch meal types
$meal_types_stmt = $conn->prepare("
    SELECT mt.name 
    FROM recipe_meal_types rmt 
    JOIN meal_types mt ON rmt.meal_type_id = mt.meal_id 
    WHERE rmt.recipe_id = ?
");
$meal_types_stmt->bind_param("i", $recipe_id);
$meal_types_stmt->execute();
$meal_types_result = $meal_types_stmt->get_result();
$meal_types = [];
while ($meal_type = $meal_types_result->fetch_assoc()) {
    $meal_types[] = $meal_type['name'];
}
$meal_types_stmt->close();

// Fetch dietary restrictions
$dietary_stmt = $conn->prepare("
    SELECT dr.name 
    FROM recipe_dietary_restrictions rdr
    JOIN dietary_restrictions dr ON rdr.restriction_id = dr.dietRes_id 
    WHERE rdr.recipe_id = ?
");
$dietary_stmt->bind_param("i", $recipe_id);
$dietary_stmt->execute();
$dietary_result = $dietary_stmt->get_result();
$dietary_restrictions = [];
while ($restriction = $dietary_result->fetch_assoc()) {
    $dietary_restrictions[] = $restriction['name'];
}
$dietary_stmt->close();

// Fetch ingredients
$ingredients_stmt = $conn->prepare("
    SELECT i.name, ri.quantity 
    FROM recipe_ingredients ri 
    JOIN ingredients i ON ri.ingredient_id = i.ingredient_id 
    WHERE ri.recipe_id = ?
");
$ingredients_stmt->bind_param("i", $recipe_id);
$ingredients_stmt->execute();
$ingredients_result = $ingredients_stmt->get_result();

// Fetch instructions
$instructions_stmt = $conn->prepare("
    SELECT step_number, description 
    FROM instructions 
    WHERE recipe_id = ? 
    ORDER BY step_number
");
$instructions_stmt->bind_param("i", $recipe_id);
$instructions_stmt->execute();
$instructions_result = $instructions_stmt->get_result();

// Fetch ratings
$ratings_stmt = $conn->prepare("
    SELECT u.username, r.rating, r.comment, r.created_at 
    FROM ratings r 
    JOIN users u ON u.user_id = r.user_id 
    WHERE r.recipe_id = ? 
    ORDER BY r.created_at DESC
");
$ratings_stmt->bind_param("i", $recipe_id);
$ratings_stmt->execute();
$ratings_result = $ratings_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($recipe['NAME']) ?></title>
    <style>
        .bookmark-container {
            position: relative;
            margin: 20px 0;
        }
        .bookmarks-btn {
            top: 1rem;
            right: 1rem;
            min-width: 2.5rem;  
            width: auto;
            height: 2.5rem;
            padding: 10px 20px;
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            display: inline-flex;
            position: static !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 9999 !important;
            align-items: center;
            justify-content: center;
        }
        .recipe-meta-box {
            border: 1px solid #ccc;
            padding: 15px;
            margin-top: 20px;
            border-radius: 8px;
            background-color: rgb(255, 252, 252);
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            max-width: 100%;
        }
        .recipe-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="main-content">
    <div class="recipe-detail">
        <div class="recipe-header">
            <h1><?= htmlspecialchars($recipe['NAME']) ?></h1>
            <div class="bookmark-container">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?id=<?= $recipe_id ?>">
                        <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
                        <input type="hidden" name="action" value="<?= isset($bookmarkedRecipes[$recipe_id]) ? 'remove' : 'add' ?>">
                        <button type="submit" name="toggle_bookmark" class="bookmarks-btn <?= isset($bookmarkedRecipes[$recipe_id]) ? 'active' : '' ?>">
                            <?= isset($bookmarkedRecipes[$recipe_id]) ? '★ Bookmarked' : '☆ Bookmark' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <button class="bookmark-btn" onclick="alert('Please login to bookmark recipes')">
                        ☆ Bookmark
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="recipe-description">
            <?php if (!empty($recipe['DESCRIPTION'])): ?>
                <p class="recipe-description"><?= htmlspecialchars($recipe['DESCRIPTION']) ?></p>
            <?php endif; ?>
        </div>
        
        <div class="recipe-image">   
            <?php
            $imagePath = (!empty($recipe['IMAGE_URL']) && $recipe['IMAGE_URL'] != 'default.png') 
                ? htmlspecialchars($recipe['IMAGE_URL']) 
                : '/Recipes-main/images/default.jpg';
            ?>
            <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($recipe['NAME']) ?>" onerror="this.src='/Recipes-main/images/default.png'">
        </div>
        
        <div class="recipe-meta-box">
            <p><strong>By:</strong> <?= htmlspecialchars($recipe['username'] ?? 'System') ?></p>
            <p><strong>Cuisine:</strong> <?= !empty($cuisines) ? htmlspecialchars(implode(', ', $cuisines)) : 'Not specified' ?></p>
            <p><strong>Meal Type:</strong> <?= !empty($meal_types) ? htmlspecialchars(implode(', ', $meal_types)) : 'Not specified' ?></p>
            <p><strong>Dietary Restrictions:</strong> <?= !empty($dietary_restrictions) ? htmlspecialchars(implode(', ', $dietary_restrictions)) : 'None' ?></p>
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

        <?php if (!$is_private): ?>
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
                        <textarea name="comment" id="comment" rows="4" cols="50"></textarea><br><br>
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
                            <p class="review-author"><?= htmlspecialchars($review['username']) ?></p>
                            <p class="review-comment"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                            <small class="review-date">Posted on <?= date('j/n/Y', strtotime($review['created_at'])) ?></small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No reviews yet.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
<?php include 'c:/xampp/htdocs/Recipes-main/partials/footer.php'; ?>
