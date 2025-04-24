<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

require "c:/xampp/htdocs/Recipes-main/config/db.php";

$user_id = $_SESSION['user_id'];
$sql = "
    SELECT u.recipe_id, u.name, u.image_url, 'user' as source
    FROM bookmarks b 
    JOIN user_submitted_recipes u ON b.user_id = u.user_id
    WHERE b.user_id = ? AND b.recipe_source = 'user'

    UNION

    SELECT r.recipe_id, r.name, r.image_url, 'system' as source
    FROM bookmarks b 
    JOIN recipes r ON b.recipe_id = r.recipe_id 
    WHERE b.user_id = ? AND b.recipe_source = 'system'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<?php include '../partials/header.php'; ?>
<main class="main-content">
    <h1>My Bookmarked Recipes</h1>

    <?php if (isset($_GET['success']) && $_GET['success'] === "recipe_bookmarked"): ?>
        <div class="alert alert-success">🎉 Recipe added and bookmarked successfully!</div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <div class="recipes-grid" id="recipe-results">
            <?php while($row = $result->fetch_assoc()): ?>
                    <div class="recipe-card">
                        <div class="recipe-image">
                            <img src="<?= htmlspecialchars($row['image_url']) ?: 'default.jpg' ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                            <button class="bookmark-btn" onclick="toggleBookmark(this)">
                                <i class="fas fa-bookmark"></i>
                            </button>
                        </div>
                    <div class="recipe-content">
                        <h3 class="recipe-title">
                            <a href="recipe_detail.php?id=<?= $row['recipe_id'] ?>">
                                <?= htmlspecialchars($row['name']) ?>
                            </a>
                        </h3>
                    </div>
                    </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</main>


<?php include '../partials/footer.php'; ?>
