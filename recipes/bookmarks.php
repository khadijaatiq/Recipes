<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

require "../config/db.php";

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT u.recipe_id, u.name, '' as image_url, 'user' as source
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
<div class="container">
    <h1>My Bookmarked Recipes</h1>

    <?php if (isset($_GET['success']) && $_GET['success'] === "recipe_bookmarked"): ?>
        <div class="alert alert-success">🎉 Recipe added and bookmarked successfully!</div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <div class="recipes-grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="recipe-card">
                    <div class="recipe-image">
                        <?php if ($row['source'] === 'system'): ?>
                            <img src="<?= htmlspecialchars($row['image_url']); ?>" alt="<?= htmlspecialchars($row['name']); ?>">
                        <?php else: ?>
                            <img src="<?= !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : '/images/default.jpg' ?>" alt="<?= htmlspecialchars($row['name']); ?>">
                        <?php endif; ?>
                    </div>
                    <h3><?= htmlspecialchars($row['name']); ?></h3>
                    <a href="view_recipe.php?id=<?= $row['recipe_id']; ?>&source=<?= $row['source']; ?>">View Recipe</a>
                </div>
            <?php endwhile; ?>

        </div>
    <?php else: ?>
        <p>No recipes bookmarked yet!</p>
    <?php endif; ?>
</div>

<?php include '../partials/footer.php'; ?>