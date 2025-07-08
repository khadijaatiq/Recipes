can you just integrate this logic here because whats happening is its spinning and it says bookmark could not be added but its adding in the bookmarks page which means it is being added correctly and the bookmark icon isn't being filled so <?php 
session_start();
include '../partials/header.php'; ?>
<?php
require_once 'c:/xampp/htdocs/Recipes-main/config/db.php';

if (!file_exists('c:/xampp/htdocs/Recipes-main/auth/database.php')) {
    die("Database connection file not found!");
}

require_once 'c:/xampp/htdocs/Recipes-main/auth/database.php';

if ($conn->connect_errno) {
    die("Database connection failed: " . $conn->connect_error);
}

if (isset($_POST['toggle_bookmark'])) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        die();
    }

    $userId = $_SESSION['user_id'];
    $recipeId = (int)$_POST['recipe_id'];
    $recipeSource = $_POST['recipe_source'] === 'system' ? 'system' : 'user';
    $action = $_POST['action'];

    if ($action === 'add') {
        // Add to bookmarks
        $stmt = $conn->prepare("INSERT INTO bookmarks (user_id, recipe_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $userId, $recipeId);
        if ($stmt->execute()) {
            // Log the bookmark action
            $logStmt = $conn->prepare("INSERT INTO bookmark_logs (user_id, recipe_id) VALUES (?, ?)");
            $logStmt->bind_param("ii", $userId, $recipeId);
            $logStmt->execute();
            $logStmt->close();
        }
    } else {
        // Remove from bookmarks
        $stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND recipe_id = ?");
        $stmt->bind_param("ii", $userId, $recipeId);
        $stmt->execute();
    }
    $stmt->close();
    
    // Redirect back to prevent form resubmission
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Get user's bookmarks if logged in
$bookmarkedRecipes = [];
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $bookmarkStmt = $conn->prepare("SELECT recipe_id FROM bookmarks WHERE user_id = ?");
    $bookmarkStmt->bind_param("i", $userId);
    $bookmarkStmt->execute();
    $bookmarkResult = $bookmarkStmt->get_result();
    
    while ($row = $bookmarkResult->fetch_assoc()) {
        $bookmarkedRecipes[$row['recipe_id']] = true;
    }
    $bookmarkStmt->close();
}

// Base query for system recipes
$query = "
    SELECT 
        r.RECIPE_ID as recipe_id, 
        r.NAME as name, 
        r.IMAGE_URL as image_url, 
        IFNULL(AVG(rt.rating), 0) AS rating,
        r.source
    FROM recipes r
    LEFT JOIN ratings rt ON r.RECIPE_ID = rt.recipe_id
    WHERE r.source = 'system'
";

// Initialize variables for filters
$params = [];
$types = '';
$conditions = [];
$userParams = [];
$userTypes = '';
$userConditions = [];

// SEARCH FILTER
if (!empty($_GET['search'])) {
    $conditions[] = " (r.NAME LIKE ? OR r.DESCRIPTION LIKE ?)";
    $searchTerm = "%" . $_GET['search'] . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

// MEAL TYPE FILTER (as checkboxes)
if (!empty($_GET['meal'])) {
    $mealTypes = (array)$_GET['meal'];
    $placeholders = implode(',', array_fill(0, count($mealTypes), '?'));
    $conditions[] = " r.RECIPE_ID IN (
        SELECT rm.recipe_id FROM recipe_meal_types rm 
        JOIN meal_types m ON rm.meal_type_id = m.id 
        WHERE m.name IN ($placeholders))
    ";
    $params = array_merge($params, $mealTypes);
    $types .= str_repeat('s', count($mealTypes));
}

// CUISINE FILTER (as checkboxes)
if (!empty($_GET['cuisine'])) {
    $cuisines = (array)$_GET['cuisine'];
    $placeholders = implode(',', array_fill(0, count($cuisines), '?'));
    $conditions[] = " r.RECIPE_ID IN (
        SELECT rc.recipe_id FROM recipe_cuisines rc 
        JOIN cuisines c ON rc.cuisine_id = c.cuisine_id
        WHERE c.name IN ($placeholders))";
    $params = array_merge($params, $cuisines);
    $types .= str_repeat('s', count($cuisines));
}

// DIETARY RESTRICTIONS FILTER
if (!empty($_GET['diet'])) {
    $diets = (array)$_GET['diet'];
    $placeholders = implode(',', array_fill(0, count($diets), '?'));
    $conditions[] = " r.RECIPE_ID IN (
        SELECT rd.recipe_id FROM recipe_dietary_restrictions rd
        JOIN dietary_restrictions d ON rd.restriction_id = d.id
        WHERE d.name IN ($placeholders))";
    $params = array_merge($params, $diets);
    $types .= str_repeat('s', count($diets));
}

// MAX COOK TIME FILTER
if (!empty($_GET['max_time'])) {
    $conditions[] = " r.COOKING_TIME <= ?";
    $params[] = (int)$_GET['max_time'];
    $types .= 'i';
}

if (!empty($_GET['min_rating'])) {
    $conditions[] = "rating >= ?";
    $params[] = (float)$_GET['min_rating'];
    $types .= 'd';
}

// Add WHERE clause if there are conditions
if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " GROUP BY r.RECIPE_ID, r.NAME, r.IMAGE_URL, r.source";

// UNION with user recipes
$query .= "
    UNION
    
    SELECT 
        r.RECIPE_ID as recipe_id, 
        r.NAME as name, 
        r.IMAGE_URL as image_url, 
        IFNULL(AVG(rt.rating), 0) AS rating,
        r.source
    FROM recipes r
    LEFT JOIN ratings rt ON r.RECIPE_ID = rt.recipe_id
    WHERE r.source = 'user' AND r.status='public'";

// USER RECIPE FILTERS
// Search
if (!empty($_GET['search'])) {
    $userConditions[] = " (r.NAME LIKE ? OR r.DESCRIPTION LIKE ?)";
    $searchTerm = "%" . $_GET['search'] . "%";
    $userParams[] = $searchTerm;
    $userParams[] = $searchTerm;
    $userTypes .= 'ss';
}

// Meal Type
if (!empty($_GET['meal'])) {
    $mealTypes = (array)$_GET['meal'];
    $placeholders = implode(',', array_fill(0, count($mealTypes), '?'));
    $userConditions[] = " r.RECIPE_ID IN (
        SELECT rm.recipe_id FROM recipe_meal_types rm 
        JOIN meal_types m ON rm.meal_type_id = m.id 
        WHERE m.name IN ($placeholders))
    ";
    $userParams = array_merge($userParams, $mealTypes);
    $userTypes .= str_repeat('s', count($mealTypes));
}

// Cuisine
if (!empty($_GET['cuisine'])) {
    $cuisines = (array)$_GET['cuisine'];
    $placeholders = implode(',', array_fill(0, count($cuisines), '?'));
    $userConditions[] = " r.RECIPE_ID IN (
        SELECT rc.recipe_id FROM recipe_cuisines rc 
        JOIN cuisines c ON rc.cuisine_id = c.cuisine_id
        WHERE c.name IN ($placeholders))";
    $userParams = array_merge($userParams, $cuisines);
    $userTypes .= str_repeat('s', count($cuisines));
}

// Dietary Restrictions
if (!empty($_GET['diet'])) {
    $diets = (array)$_GET['diet'];
    $placeholders = implode(',', array_fill(0, count($diets), '?'));
    $userConditions[] = " r.RECIPE_ID IN (
        SELECT rd.recipe_id FROM recipe_dietary_restrictions rd
        JOIN dietary_restrictions d ON rd.restriction_id = d.id
        WHERE d.name IN ($placeholders))";
    $userParams = array_merge($userParams, $diets);
    $userTypes .= str_repeat('s', count($diets));
}

// Max Cook Time
if (!empty($_GET['max_time'])) {
    $userConditions[] = " r.COOKING_TIME <= ?";
    $userParams[] = (int)$_GET['max_time'];
    $userTypes .= 'i';
}

if (!empty($_GET['min_rating'])) {
    $userConditions[] = " rating >= ?";
    $userParams[] = (float)$_GET['min_rating'];
    $userTypes .= 'd';
}

// Add conditions to user part of query
if (!empty($userConditions)) {
    $query .= " AND " . implode(" AND ", $userConditions);
}

$query .= " GROUP BY r.RECIPE_ID, r.NAME, r.IMAGE_URL, r.source";
// Prepare and execute the query
$stmt = $conn->prepare($query);

if (!empty($params) || !empty($userParams)) {
    $allParams = array_merge($params, $userParams);
    
    // Combine all type definitions
    $allTypes = $types . $userTypes;
    
    // Prepend the type string as the first parameter
    $bindParams = array_merge([$allTypes], $allParams);
    
    // Create references for bind_param
    $refs = [];
    foreach($bindParams as $key => $value) {
        $refs[$key] = &$bindParams[$key];
    }
    
    // Call bind_param with the references
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

$stmt->execute();
$result = $stmt->get_result();
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
                <form id="filter-form" method="GET" action="">
                    <div class="search-bar">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" id="recipe-search" placeholder="Search recipes" 
                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                        <button id="filters-button" class="filters-button" type="button">
                            <i class="fas fa-sliders-h"></i>
                            <span>Filters</span>
                            <span class="filter-badge" id="filter-count" style="display: none;">0</span>
                        </button>
                    </div>

                    <div class="filters-panel" id="filters-panel">
                        <div class="filters-header">
                            <h3>Filters</h3>
                            <button id="reset-filters" class="text-button" type="button">Reset all</button>
                        </div>
                        <div class="filters-section">
                            <h4><i class="fas fa-utensils"></i> Meal Type</h4>
                            <div class="filter-checkboxes">
                                <?php 
                                $meal_types = ['Beverage','Sauce','Side Dish','Breakfast', 'Lunch', 'Dinner', 'Snack', 'Dessert', 'Brunch'];
                                foreach($meal_types as $type): 
                                    $checked = isset($_GET['meal']) && in_array($type, (array)$_GET['meal']) ? 'checked' : '';
                                ?>
                                    <label class="filter-checkbox">
                                        <input type="checkbox" name="meal[]" value="<?= $type ?>" <?= $checked ?>>
                                        <?= $type ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- CUISINE FILTER (as checkboxes) -->
                        <div class="filters-section">
                            <h4><i class="fas fa-globe"></i> Cuisine</h4>
    <div class="filter-checkboxes">
        <?php 
        $cuisines = ['Italian', 'Chinese', 'Indian', 'Mexican', 'Thai', 'Mediterranean', 'Japanese', 'American', 'French', 'Other'];
        foreach($cuisines as $cuisine): 
            $checked = isset($_GET['cuisine']) && $_GET['cuisine'] == $cuisine ? 'checked' : '';
        ?>
            <label class="filter-checkbox">
                <input type="radio" name="cuisine" value="<?= $cuisine ?>" <?= $checked ?>>
                <?= $cuisine ?>
            </label>
        <?php endforeach; ?>
    </div>
</div>
                        <!-- DIETARY RESTRICTIONS FILTER -->
                        <div class="filters-section">
                            <h4><i class="fas fa-leaf"></i> Dietary Restrictions</h4>
                            <div class="filter-checkboxes">
                                <?php 
                                $diets = ['Vegetarian', 'Vegan', 'Gluten-Free', 'Dairy-Free', 'Keto', 'Low-Carb'];
                                foreach($diets as $diet): 
                                    $checked = isset($_GET['diet']) && in_array($diet, (array)$_GET['diet']) ? 'checked' : '';
                                ?>
                                    <label class="filter-checkbox">
                                        <input type="checkbox" name="diet[]" value="<?= $diet ?>" <?= $checked ?>>
                                        <?= $diet ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="filters-section">
                            <h4><i class="fas fa-clock"></i> Max Cook Time</h4>
                            <div class="range-slider">
                                <input type="range" name="max_time" id="time-slider" min="15" max="120" 
                                       value="<?= isset($_GET['max_time']) ? $_GET['max_time'] : '60' ?>" class="slider">
                                <div class="slider-values">
                                    <span>15min</span>
                                    <span id="time-value"><?= isset($_GET['max_time']) ? $_GET['max_time'].'min' : '60min' ?></span>
                                    <span>120min</span>
                                </div>
                            </div>
                        </div>
                        <div class="filters-section">
                            <h4><i class="fas fa-star"></i> Minimum Rating</h4>
                            <div class="range-slider">
                                <input type="range" name="min_rating" id="rating-slider" min="0" max="5" step="0.5" 
                                    value="<?= isset($_GET['min_rating']) ? $_GET['min_rating'] : '0' ?>" class="slider">
                                <div class="slider-values">
                                    <span>0</span>
                                    <span id="rating-value"><?= isset($_GET['min_rating']) ? $_GET['min_rating'] : '3' ?></span>
                                    <span>5</span>
                                </div>
                            </div>
                        </div>
                        <div class="filters-actions">
                            <button type="submit" class="button button-primary">Apply Filters</button>
                        </div>
                    </div>
                </form>
                
                <div class="active-filters" id="active-filters">
                    <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                        <div class="active-filter">
                            Search: "<?= htmlspecialchars($_GET['search']) ?>"
                            <span class="remove-filter" data-name="search">×</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_GET['meal'])): ?>
                        <?php foreach((array)$_GET['meal'] as $meal): ?>
                            <div class="active-filter">
                                <?= htmlspecialchars($meal) ?>
                                <span class="remove-filter" data-name="meal[]" data-value="<?= htmlspecialchars($meal) ?>">×</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if(isset($_GET['cuisine']) && !empty($_GET['cuisine'])): ?>
    <div class="active-filter">
        <?= htmlspecialchars($_GET['cuisine']) ?>
        <span class="remove-filter" data-name="cuisine" data-value="">×</span>
    </div>
<?php endif; ?>
                    
                    <?php if(isset($_GET['diet'])): ?>
                        <?php foreach((array)$_GET['diet'] as $diet): ?>
                            <div class="active-filter">
                                <?= htmlspecialchars($diet) ?>
                                <span class="remove-filter" data-name="diet[]" data-value="<?= htmlspecialchars($diet) ?>">×</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if(isset($_GET['max_time']) && $_GET['max_time'] != 60): ?>
                        <div class="active-filter">
                            Max <?= (int)$_GET['max_time'] ?>min
                            <span class="remove-filter" data-name="max_time" data-value="60">×</span>
                        </div>
                    <?php endif; ?>
                    <?php if(isset($_GET['min_rating']) && $_GET['min_rating'] > 0): ?>
                        <div class="active-filter">
                            Min <?= (float)$_GET['min_rating'] ?> stars
                        <span class="remove-filter" data-name="min_rating" data-value="0">×</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="recipes-grid" id="recipe-results">
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="recipe-card">
                    <?php
            // Improved image path handling with fallback
            $imagePath = (!empty($row['image_url']) && $row['image_url'] != 'default.jpg' )
                ? htmlspecialchars($row['image_url']) 
                : '/Recipes-main/images/default.jpg';
            ?>
            <div class="recipe-image">
                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($row['name']) ?>" onerror="this.src='/Recipes-main/images/default.png'">
                            <?php if(isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                        <input type="hidden" name="recipe_id" value="<?= $row['recipe_id'] ?>">
                        <input type="hidden" name="recipe_source" value="<?= $row['source'] ?>">
                        <input type="hidden" name="action" value="<?= isset($bookmarkedRecipes[$row['recipe_id']]) ? 'remove' : 'add' ?>">
                        <button type="submit" name="toggle_bookmark" class="bookmark-btn <?= isset($bookmarkedRecipes[$row['recipe_id']]) ? 'active' : '' ?>">
                            <i class="<?= isset($bookmarkedRecipes[$row['recipe_id']]) ? 'fas' : 'far' ?> fa-bookmark"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <button class="bookmark-btn" onclick="alert('Please login to bookmark recipes')">
                        <i class="far fa-bookmark"></i>
                    </button>
                <?php endif; ?>
                        </div>
                        <div class="recipe-content">
                            <h3 class="recipe-title">
                                <a href="recipe_detail.php?id=<?= $row['recipe_id'] ?>">
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
    </section>
</main>

<script>
    
    // Toggle filter panel
    const panel = document.getElementById('filters-panel');
    const filterBtn = document.getElementById('filters-button');
    filterBtn.addEventListener('click', () => {
        panel.classList.toggle('active');
    });

    // Update time slider display
    const timeSlider = document.getElementById('time-slider');
    const timeValue = document.getElementById('time-value');
    timeSlider.addEventListener('input', () => {
        timeValue.textContent = timeSlider.value + 'min';
    });
    const ratingSlider = document.getElementById('rating-slider');
    const ratingValue = document.getElementById('rating-value');
    ratingSlider.addEventListener('input', () => {
    ratingValue.textContent = ratingSlider.value;
    });
    document.addEventListener('DOMContentLoaded', function() {
        const bookmarkForms = document.querySelectorAll('.bookmark-form');
        
        bookmarkForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const button = this.querySelector('.bookmark-btn');
                const icon = button.querySelector('i');
                const actionInput = this.querySelector('input[name="action"]');
                
                // Toggle UI immediately
                button.classList.toggle('active');
                icon.classList.toggle('fas');
                icon.classList.toggle('far');
                
                // Toggle the action value
                actionInput.value = actionInput.value === 'add' ? 'remove' : 'add';
                
                // Submit the form in the background
                fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this)
                }).catch(error => {
                    console.error('Error:', error);
                    // Revert UI if error occurs
                    button.classList.toggle('active');
                    icon.classList.toggle('fas');
                    icon.classList.toggle('far');
                    actionInput.value = actionInput.value === 'add' ? 'remove' : 'add';
                });
            });
        });
    });

    // Remove filter
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-filter')) {
            const name = e.target.getAttribute('data-name');
            const value = e.target.getAttribute('data-value');
            if (name === 'cuisine') {
            const radio = form.querySelector(`input[type="radio"][name="${name}"][value="${value}"]`);
            if (radio) radio.checked = false;
            // Submit the form without the removed filter
            const form = document.getElementById('filter-form');
            if (name.includes('[]')) {
            // Remove this specific value from the array
            const inputs = form.querySelectorAll(`input[name="${name}"]`);
            }
            inputs.forEach(input => {
                if (input.value === value) {
                    input.remove();
                }
            });
        } else {
            // For non-array parameters (search, max_time)
            const input = form.querySelector(`input[name="${name}"]`);
            if (input) {
                if (name === 'max_time') {
                    input.value = '60'; // Reset to default
                } else {
                    input.remove();
                }
            }
        }
            form.submit();
        }
    });

    // Reset all filters
    document.getElementById('reset-filters').addEventListener('click', () => {
        window.location.href = window.location.pathname;
    });
</script>

<?php include '../partials/footer.php'; ?>
