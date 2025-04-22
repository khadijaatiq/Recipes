<?php include '../partials/header.php'; ?>
<?php
require_once 'c:/xampp/htdocs/ProjectDB/config/db.php';

if (!file_exists('c:/xampp/htdocs/ProjectDB/auth/database.php')) {
    die("Database connection file not found!");
}

require_once 'c:/xampp/htdocs/ProjectDB/auth/database.php';

if ($conn->connect_errno) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch recipes
$sql = "SELECT r.recipe_id, r.name, r.image_url, IFNULL(AVG(rt.rating), 0) AS rating FROM  recipes r LEFT JOIN  ratings rt ON r.recipe_id = rt.recipe_id GROUP BY r.recipe_id, r.name, r.image_url";
$result = $conn->query($sql);
?>

<script>
function toggleBookmark(el) {
    el.classList.toggle('active');
    const icon = el.querySelector('i');
    icon.classList.toggle('fas'); // filled
    icon.classList.toggle('far'); // outline
}
</script>

<main class="main-content">
        <section class="section recipes-section">
            <div class="container">
                <div class="section-header">
                    <h1 class="section-title">Find Your Perfect Recipe</h1>
                    <p class="section-subtitle">Search by ingredients, meal type, cooking time, or dietary needs</p>
                </div>

                <div class="search-container">
                    <div class="search-bar">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="recipe-search" placeholder="Search recipes or ingredients...">
                        <button id="filters-button" class="filters-button">
                            <i class="fas fa-sliders-h"></i>
                            <span>Filters</span>
                            <span class="filter-badge" id="filter-count" style="display: none;">0</span>
                        </button>
                    </div>

                    <div class="filters-panel" id="filters-panel">
                        <div class="filters-header">
                            <h3>Filters</h3>
                            <button id="reset-filters" class="text-button">Reset all</button>
                        </div>
                        <div class="filters-section">
                            <h4><i class="fas fa-utensils"></i> Meal Type</h4>
                            <div class="filter-options" id="meal-type-options">
                                <span class="filter-badge" data-filter="meal" data-value="Breakfast">Breakfast</span>
                                <span class="filter-badge" data-filter="meal" data-value="Lunch">Lunch</span>
                                <span class="filter-badge" data-filter="meal" data-value="Dinner">Dinner</span>
                                <span class="filter-badge" data-filter="meal" data-value="Snack">Snack</span>
                                <span class="filter-badge" data-filter="meal" data-value="Dessert">Dessert</span>
                                <span class="filter-badge" data-filter="meal" data-value="Brunch">Brunch</span>
                            </div>
                        </div>
                        <div class="filters-section">
                            <h4><i class="fas fa-globe"></i> Cuisine</h4>
                            <div class="filter-options" id="cuisine-options">
                                <span class="filter-badge" data-filter="cuisine" data-value="Italian">Italian</span>
                                <span class="filter-badge" data-filter="cuisine" data-value="Chinese">Chinese</span>
                                <span class="filter-badge" data-filter="cuisine" data-value="Indian">Indian</span>
                                <span class="filter-badge" data-filter="cuisine" data-value="Mexican">Mexican</span>
                                <span class="filter-badge" data-filter="cuisine" data-value="Thai">Thai</span>
                                <span class="filter-badge" data-filter="cuisine" data-value="Mediterranean">Mediterranean</span>
                            </div>
                        </div>
                        <div class="filters-section">
                            <h4><i class="fas fa-leaf"></i> Dietary Restrictions</h4>
                            <div class="filter-checkboxes" id="diet-options">
                                <label class="filter-checkbox">
                                    <input type="checkbox" data-filter="diet" data-value="Vegetarian"> Vegetarian
                                </label>
                                <label class="filter-checkbox">
                                    <input type="checkbox" data-filter="diet" data-value="Vegan"> Vegan
                                </label>
                                <label class="filter-checkbox">
                                    <input type="checkbox" data-filter="diet" data-value="Gluten-Free"> Gluten-Free
                                </label>
                                <label class="filter-checkbox">
                                    <input type="checkbox" data-filter="diet" data-value="Dairy-Free"> Dairy-Free
                                </label>
                                <label class="filter-checkbox">
                                    <input type="checkbox" data-filter="diet" data-value="Keto"> Keto
                                </label>
                                <label class="filter-checkbox">
                                    <input type="checkbox" data-filter="diet" data-value="Low-Carb"> Low-Carb
                                </label>
                            </div>
                        </div>
                        <div class="filters-section">
                            <h4><i class="fas fa-clock"></i> Max Cook Time</h4>
                            <div class="range-slider">
                                <input type="range" id="time-slider" min="15" max="120" value="60" class="slider">
                                <div class="slider-values">
                                    <span>15min</span>
                                    <span id="time-value">60min</span>
                                    <span>120min</span>
                                </div>
                            </div>
                        </div>
                        <div class="filters-section pantry-match">
                            <h4><i class="fas fa-kitchen-set"></i> Match with Pantry</h4>
                            <button id="match-pantry" class="button button-outline">Find Matching Recipes</button>
                        </div>
                    </div>

                    <div class="active-filters" id="active-filters">
                        <!-- Active filters will be added here dynamically -->
                         
                    </div>
                </div>

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
                <a href="recipe-detail.php?id=<?= $row['recipe_id'] ?>">
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

                </div>
            </div>
        </section>
    </main>
<?php include '../partials/footer.php'; ?>

