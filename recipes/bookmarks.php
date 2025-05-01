<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require "c:/xampp/htdocs/Recipes-main/config/db.php";

if (isset($_GET['success'])): ?>
    <div class="alert alert-success" id="success-message">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif;

$user_id = $_SESSION['user_id'];
$sql = "
SELECT r.RECIPE_ID as recipe_id, r.name, r.image_url, r.user_id as owner_id, 
       CASE WHEN r.source = 'user' THEN 'user' ELSE 'system' END as source
FROM bookmarks b 
JOIN recipes r ON b.recipe_id = r.RECIPE_ID
WHERE b.user_id = ?
ORDER BY r.name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include '../partials/header.php'; ?>
<main class="main-content">
    <h1>My Bookmarked Recipes</h1>

    <?php if (isset($_GET['success']) && $_GET['success'] === "recipe_bookmarked"): ?>
        <div class="alert alert-success">🎉 Recipe added and bookmarked successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['success']) && $_GET['success'] === "unbookmarked"): ?>
        <div class="alert alert-success">Bookmark removed.</div>
    <?php endif; ?>
    <?php if (isset($_GET['success']) && $_GET['success'] === "deleted"): ?>
        <div class="alert alert-success">Recipe deleted.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === "not_owner"): ?>
        <div class="alert alert-danger">You are not allowed to delete this recipe.</div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <div class="recipes-grid" id="recipe-results">
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="recipe-card" style="position:relative;">
                    <div class="recipe-image">
                        <img src="<?= htmlspecialchars($row['image_url']) ?: 'default.jpg' ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                        <?php if ($row['source'] === 'user' && $row['owner_id'] == $user_id): ?>
                            <!-- Edit/Delete for user-submitted recipes (only for owner) -->
                            <div class="user-recipe-actions" style="display:none; position:absolute; top:10px; right:10px; z-index:2;">
                                <a href="edit_recipe.php?id=<?= $row['recipe_id'] ?>" class="edit-btn" title="Edit"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="bookmarks_process.php" style="display:inline;">
                                    <input type="hidden" name="recipe_source" value="user">
                                </form>
                                <form method="POST" action="delete_recipe.php" style="display:inline;">
                                    <input type="hidden" name="recipe_id" value="<?= $row['recipe_id'] ?>">
                                    <button type="submit" class="delete-btn" title="Delete" onclick="return confirm('Are you sure you want to delete this recipe?');">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($row['source'] === 'system'): ?>
                            <!-- Unbookmark for system recipes -->
                            <form method="POST" action="bookmarks_process.php" style="position:absolute; top:10px; right:10px;">
                                <input type="hidden" name="recipe_id" value="<?= $row['recipe_id'] ?>">
                                <input type="hidden" name="recipe_source" value="system">
                                <button type="submit" name="unbookmark" class="bookmark-btn" title="Remove Bookmark">
                                    <i class="fas fa-bookmark"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="recipe-content">
                        <h3 class="recipe-title">
                            <a href="recipe_detail.php?id=<?= $row['recipe_id'] ?>&source=<?= $row['source'] ?>">
                                <?= htmlspecialchars($row['name']) ?>
                            </a>
                        </h3>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No bookmarks yet.</p>
    <?php endif; ?>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const successMessage = document.getElementById('success-message');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.opacity = '0';
            setTimeout(() => {
                successMessage.remove();
            }, 500); // Wait for fade-out to complete
        }, 3000); // Wait for 3 seconds before fading out
    }
});
</script>

<style>
.recipe-card:hover .user-recipe-actions {
    display: block !important;
}
.edit-btn, .delete-btn {
    background: none;
    border: none;
    color: #333;
    font-size: 18px;
    margin-left: 5px;
    cursor: pointer;
}
.edit-btn:hover, .delete-btn:hover {
    color: #d9534f;
}
#success-message {
    transition: opacity 0.5s ease;
}
</style>
<?php include '../partials/footer.php'; ?>
