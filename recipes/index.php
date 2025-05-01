<?php include 'c:/xampp/htdocs/Recipes-main/partials/header.php'; 
require_once 'c:/xampp/htdocs/Recipes-main/config/db.php';?>
<?php
$sql = "
    SELECT 
        r.RECIPE_ID as recipe_id, 
        r.name, 
        r.image_url, 
        IFNULL(AVG(rt.rating), 0) AS rating,
        COUNT(rt.rating) AS num_ratings,
        r.source
    FROM recipes r
    LEFT JOIN ratings rt ON r.RECIPE_ID = rt.recipe_id
    WHERE r.source = 'system' OR (r.source = 'user' AND r.status = 'public')
    GROUP BY r.RECIPE_ID, r.name, r.image_url, r.source
    ORDER BY num_ratings DESC, rating DESC
    LIMIT 4"; 
$result = $conn->query($sql);
if (!$result) {
    die("Query Failed: " . $conn->error);
}
?>


<main class="main-content">
        <section class="hero-section">
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">Cook what you already have</h1>
                    <p class="hero-subtitle">Discover perfect recipes based on the ingredients in your kitchen.</p>
                    <div class="hero-actions">
                        <a href="all_recipes.php" class="button button-primary">Find Recipes</a>
                        <a href="#features" class="button button-outline">See How It Works</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="features">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">How It Works</h2>
                    <p class="section-subtitle">CookCraft makes cooking simple and personalized</p>
                </div>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-carrot"></i>
                        </div>
                        <h3 class="feature-title">Ingredient-Based</h3>
                        <p class="feature-description">Find recipes using what you already have in your pantry.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="feature-title">Time-Based</h3>
                        <p class="feature-description">Filter recipes by total cooking time to find quick meals.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h3 class="feature-title">Dietary Filters</h3>
                        <p class="feature-description">Find recipes that match your dietary preferences and restrictions.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Popular Recipes</h2>
                    <p class="section-subtitle">Trending recipes our community loves</p>
                </div>
                <div class="recipes-grid" id="popular-recipes">
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="recipe-card">
                   <?php $imagePath = (!empty($row['image_url']) && $row['image_url'] != 'default.jpg' )
                ? htmlspecialchars($row['image_url']) 
                : '/Recipes-main/images/default.jpg';
            ?>
                        <div class="recipe-image">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="bookmark-btn" onclick="toggleBookmark(<?= $row['recipe_id'] ?>, '<?= $row['source'] ?>')">
                                        <i class="fas fa-bookmark"></i>
                                    </button>
                                <?php endif; ?>
                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($row['name']) ?>" onerror="this.src='/Recipes-main/images/default.png'">
                        </div>
                        <div class="recipe-content">
                            <h3 class="recipe-title">
                                <a href="recipe_detail.php?id=<?= $row['recipe_id'] ?>&source=<?= $row['source'] ?>">
                                    <?= htmlspecialchars($row['name']) ?>
                                </a>
                            </h3>
                            <div class="recipe-meta">
                                <div class="rating">
                                    <i class="fas fa-star"></i>
                                    <span><?= number_format($row['rating'], 1) ?>/5</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="view-all-container">
                <a href="all_recipes.php" class="button button-outline">View All Recipes</a>
            </div>
        </div>
    </section>
</main>
<?php include 'c:/xampp/htdocs/Recipes-main/partials/footer.php'; ?>
<script>
function toggleBookmark(recipeId, source) {
    // AJAX call to handle bookmark toggle
    fetch('bookmark_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `recipe_id=${recipeId}&source=${source}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Update UI to reflect bookmark status
            const btn = event.target.closest('.bookmark-btn');
            btn.classList.toggle('active');
            btn.innerHTML = <i class="fas fa-bookmark${data.bookmarked ? '' : '-o'}"></i>;
        }
    });
}
</script>
