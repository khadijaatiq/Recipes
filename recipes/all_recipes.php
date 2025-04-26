<?php include '../partials/header.php'; ?>
<?php
require_once 'c:/xampp/htdocs/Recipes-main/config/db.php';

if (!file_exists('c:/xampp/htdocs/Recipes-main/auth/database.php')) {
    die("Database connection file not found!");
}

require_once 'c:/xampp/htdocs/Recipes-main/auth/database.php';

if ($conn->connect_errno) {
    die("Database connection failed: " . $conn->connect_error);
}

// Base query for system recipes
$query = "
    SELECT 
        r.recipe_id, 
        r.name, 
        r.image_url, 
        IFNULL(AVG(rt.rating), 0) AS rating,
        'system' as source
    FROM recipes r
    LEFT JOIN ratings rt ON r.recipe_id = rt.recipe_id
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
    $conditions[] = " (r.name LIKE ? OR r.description LIKE ?)";
    $searchTerm = "%" . $_GET['search'] . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

// MEAL TYPE FILTER (as checkboxes)
if (!empty($_GET['meal'])) {
    $mealTypes = (array)$_GET['meal'];
    $placeholders = implode(',', array_fill(0, count($mealTypes), '?'));
    $conditions[] = " r.recipe_id IN (
        SELECT rm.recipe_id FROM recipe_meal_types rm 
        JOIN meal_types m ON rm.meal_type_id = m.id 
        WHERE rm.recipe_source = 'system' AND m.name IN ($placeholders))
    ";
    $params = array_merge($params, $mealTypes);
    $types .= str_repeat('s', count($mealTypes));
}

// CUISINE FILTER (as checkboxes)
if (!empty($_GET['cuisine'])) {
    $cuisines = (array)$_GET['cuisine'];
    $placeholders = implode(',', array_fill(0, count($cuisines), '?'));
    $conditions[] = "  r.recipe_id IN (SELECT rc.recipe_id FROM recipe_cuisines rc 
        JOIN cuisines c ON rc.cuisine_id = c.cuisine_id
        WHERE rc.recipe_source = 'system' AND c.name IN ($placeholders))";
    $params = array_merge($params, $cuisines);
    $types .= str_repeat('s', count($cuisines));
}

// DIETARY RESTRICTIONS FILTER
if (!empty($_GET['diet'])) {
    $diets = (array)$_GET['diet'];
    $placeholders = implode(',', array_fill(0, count($diets), '?'));
    $conditions[] = " r.recipe_id IN (
        SELECT rd.recipe_id FROM recipe_dietary_restrictions rd
        JOIN dietary_restrictions d ON rd.restriction_id = d.id
        WHERE rd.recipe_source = 'system' AND d.name IN ($placeholders))";
    $params = array_merge($params, $diets);
    $types .= str_repeat('s', count($diets));
}

// MAX COOK TIME FILTER
if (!empty($_GET['max_time'])) {
    $conditions[] = " r.cooking_time <= ?";
    $params[] = (int)$_GET['max_time'];
    $types .= 'i';
}

// Add WHERE clause if there are conditions
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " GROUP BY r.recipe_id, r.name, r.image_url";

// UNION with user recipes
$query .= "
    UNION
    
    SELECT 
        usr.recipe_id, 
        usr.name, 
        usr.image_url, 
        IFNULL(AVG(rt.rating), 0) AS rating,
        'user' as source
    FROM user_submitted_recipes usr
    LEFT JOIN ratings rt ON usr.recipe_id = rt.recipe_id
    WHERE usr.status='public'";

// USER RECIPE FILTERS
// Search
if (!empty($_GET['search'])) {
    $userConditions[] = " (usr.name LIKE ? OR usr.description LIKE ?)";
    $searchTerm = "%" . $_GET['search'] . "%";
    $userParams[] = $searchTerm;
    $userParams[] = $searchTerm;
    $userTypes .= 'ss';
}

// Meal Type
if (!empty($_GET['meal'])) {
    $mealTypes = (array)$_GET['meal'];
    $placeholders = implode(',', array_fill(0, count($mealTypes), '?'));
    $userConditions[] = " usr.recipe_id IN (
        SELECT rm.recipe_id FROM recipe_meal_types rm 
        JOIN meal_types m ON rm.meal_type_id = m.id 
        WHERE rm.recipe_source = 'user' AND m.name IN ($placeholders))
    ";
    $userParams = array_merge($userParams, $mealTypes);
    $userTypes .= str_repeat('s', count($mealTypes));
}

// Cuisine
if (!empty($_GET['cuisine'])) {
    $cuisines = (array)$_GET['cuisine'];
    $placeholders = implode(',', array_fill(0, count($cuisines), '?'));
    $userConditions[] = " usr.recipe_id IN (
        SELECT rc.recipe_id FROM recipe_cuisines rc 
        JOIN cuisines c ON rc.cuisine_id = c.cuisine_id
        WHERE rc.recipe_source = 'user' AND c.name IN ($placeholders))";
    $userParams = array_merge($userParams, $cuisines);
    $userTypes .= str_repeat('s', count($cuisines));
}

// Dietary Restrictions
if (!empty($_GET['diet'])) {
    $diets = (array)$_GET['diet'];
    $placeholders = implode(',', array_fill(0, count($diets), '?'));
    $userConditions[] = " usr.recipe_id IN (
        SELECT rd.recipe_id FROM recipe_dietary_restrictions rd
        JOIN dietary_restrictions d ON rd.restriction_id = d.id
        WHERE rd.recipe_source = 'user' AND d.name IN ($placeholders))";
    $userParams = array_merge($userParams, $diets);
    $userTypes .= str_repeat('s', count($diets));
}

// Max Cook Time
if (!empty($_GET['max_time'])) {
    $userConditions[] = " usr.cooking_time <= ?";
    $userParams[] = (int)$_GET['max_time'];
    $userTypes .= 'i';
}

// Add conditions to user part of query
if (!empty($userConditions)) {
    $query .= " AND " . implode(" AND ", $userConditions);
}

$query .= " GROUP BY usr.recipe_id, usr.name, usr.image_url";
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
                        <input type="text" name="search" id="recipe-search" placeholder="Search recipes or ingredients..." 
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
                                $meal_types = ['Breakfast', 'Lunch', 'Dinner', 'Snack', 'Dessert', 'Brunch'];
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
                                    $checked = isset($_GET['cuisine']) && in_array($cuisine, (array)$_GET['cuisine']) ? 'checked' : '';
                                ?>
                                    <label class="filter-checkbox">
                                        <input type="checkbox" name="cuisine[]" value="<?= $cuisine ?>" <?= $checked ?>>
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
                    
                    <?php if(isset($_GET['cuisine'])): ?>
                        <?php foreach((array)$_GET['cuisine'] as $cuisine): ?>
                            <div class="active-filter">
                                <?= htmlspecialchars($cuisine) ?>
                                <span class="remove-filter" data-name="cuisine[]" data-value="<?= htmlspecialchars($cuisine) ?>">×</span>
                            </div>
                        <?php endforeach; ?>
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

    // Remove filter
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-filter')) {
            const name = e.target.getAttribute('data-name');
            const value = e.target.getAttribute('data-value');
            
            // Submit the form without the removed filter
            const form = document.getElementById('filter-form');
            if (name.includes('[]')) {
            // Remove this specific value from the array
            const inputs = form.querySelectorAll(`input[name="${name}"]`);
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
