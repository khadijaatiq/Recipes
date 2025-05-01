<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$conn = require "c:/xampp/htdocs/Recipes-main/config/db.php";

// Fetch ingredient options
$ingredientOptions = [];
$stmt = $conn->prepare("SELECT name FROM ingredients ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $ingredientOptions[] = $row['name'];
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add existing ingredient
    if (isset($_POST['add_existing'])) {
        $name = trim($_POST['existing_name']);
        $stmt = $conn->prepare("SELECT ingredient_id FROM ingredients WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $ingredient_id = $row['ingredient_id'];
            $stmtInsert = $conn->prepare("INSERT INTO user_ingredients (user_id, ingredient_id) VALUES (?, ?)");
            $stmtInsert->bind_param("ii", $user_id, $ingredient_id);
            $stmtInsert->execute();
            $stmtInsert->close();
        }
        $stmt->close();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }

    // Add new ingredient
    if (isset($_POST['add_new'])) {
        $name = trim($_POST['new_ingredient_name']);
        $lowerName = strtolower($name);
        $existingIngredients = [];
        $result = $conn->query("SELECT ingredient_id, name FROM ingredients");
        while ($row = $result->fetch_assoc()) {
            $existingIngredients[] = $row;
        }

        $foundMatch = false;
        foreach ($existingIngredients as $ingredient) {
            $existingName = strtolower($ingredient['name']);
            similar_text($lowerName, $existingName, $percent);
            if ($percent > 85) {
                $foundMatch = true;
                $ingredient_id = $ingredient['ingredient_id'];
                break;
            }
        }

        if (!$foundMatch) {
            $stmtInsert = $conn->prepare("INSERT INTO ingredients (name) VALUES (?)");
            $stmtInsert->bind_param("s", $name);
            $stmtInsert->execute();
            $ingredient_id = $stmtInsert->insert_id;
            $stmtInsert->close();
        }

        // Insert into user_ingredients
        $stmtInsert = $conn->prepare("INSERT INTO user_ingredients (user_id, ingredient_id) VALUES (?, ?)");
        $stmtInsert->bind_param("ii", $user_id, $ingredient_id);
        $stmtInsert->execute();
        $stmtInsert->close();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
}

$sql = "SELECT ui.ingredient_id, i.name from ingredients i JOIN user_ingredients ui ON ui.ingredient_id = i.ingredient_id where ui.user_id = ?"; 
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (isset($_POST['delete_ingredient'])) {
    $ingredient_id = intval($_POST['delete_ingredient_id']);
    $stmtDelete = $conn->prepare("DELETE FROM user_ingredients WHERE ingredient_id = ? AND user_id = ?");
    $stmtDelete->bind_param("ii", $ingredient_id, $user_id);
    $stmtDelete->execute();
    $stmtDelete->close();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

$query ="SELECT r.RECIPE_ID, r.name, IFNULL(AVG(rt.rating),0) AS rating, r.image_url,
COUNT(DISTINCT ri.ingredient_id) AS total_ingredients,
COUNT(DISTINCT CASE WHEN ui.ingredient_id IS NOT NULL THEN ri.ingredient_id END) AS matching_ingredients,
ROUND(
    COUNT(DISTINCT CASE WHEN ui.ingredient_id IS NOT NULL THEN ri.ingredient_id END) * 100.0 / 
    NULLIF(COUNT(DISTINCT ri.ingredient_id), 0),
    1
) AS match_percentage, r.source
FROM recipes r
JOIN recipe_ingredients ri ON r.RECIPE_ID = ri.recipe_id
LEFT JOIN user_ingredients ui 
    ON ui.ingredient_id = ri.ingredient_id 
    AND ui.user_id = ?
LEFT JOIN ratings rt ON r.RECIPE_ID = rt.recipe_id
WHERE r.source = 'system' OR (r.source = 'user' AND r.status = 'public')
GROUP BY r.RECIPE_ID, r.name, r.image_url, r.source
HAVING match_percentage >= ?
ORDER BY match_percentage DESC";
$threshold = isset($_POST['threshold']) ? intval($_POST['threshold']) : 0;
$fmi = $conn->prepare($query);
$fmi->bind_param("ii", $user_id, $threshold);
$fmi->execute();
$matching = $fmi->get_result();


?>
<?php include 'c:/xampp/htdocs/Recipes-main/partials/header.php'; ?>
<main class="main-content">
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h1 class="section-title">Your Pantry</h1>
                <p class="section-subtitle">Track and manage your ingredients</p>
            </div>

            <div class="pantry-navigation">
                <a href="#ingredients-tab" class="pantry-nav-link active" data-tab="ingredients-tab">My Ingredients</a>
                <a href="#matches-tab" class="pantry-nav-link" data-tab="matches-tab">Recipe Matches</a>
            </div>

            <div class="pantry-tab active" id="ingredients-tab">
                <!-- SEARCH BAR FORM -->
                <form class="search-bar" method="POST" action="">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="pantry-search" name="existing_name" list="ingredient-list" placeholder="Search ingredients..." required>
                    <datalist id="ingredient-list">
                        <?php foreach ($ingredientOptions as $option): ?>
                            <option value="<?= htmlspecialchars($option) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <button type="submit" name="add_existing" class="button button-primary">
                        <i class="fas fa-plus"></i> Add to My Pantry
                    </button>
                </form>
                <button name="add_new" class="button button-outline new-ingredient" style="align-items: center">Add New Ingredient</button>
                <div class="recipe-form-modal" id="ingredient-modal">
                    <div class="recipe-form-card">
                        <span class="close-button" id="close-ingredient">&times;</span>
                        <div class="add-ingredient-form" id="ingredient-form">
                            <h3>Add New Ingredient</h3>
                            <form id="new-ingredient-form" method="POST" action="">
                                <div class="form-group">
                                    <label for="ingredient-name">Ingredient Name</label>
                                    <input type="text" id="ingredient-name" name="new_ingredient_name" required>
                                </div>
                                <div class="form-actions">
                                    <button type="button" id="cancel-ingredient-btn" class="button button-outline">Cancel</button>
                                    <button type="submit" name="add_new" class="button button-primary">Add</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="pantry-grid" id="my-ingredients">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="pantry-item">
                            <div class="pantry-item-name">
                                <h3><?= htmlspecialchars($row['name']) ?></h3>
                            </div>
                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this ingredient from your pantry?');">
                                <input type="hidden" name="delete_ingredient_id" value="<?= $row['ingredient_id'] ?>">
                                <button type="submit" name="delete_ingredient" class="close-modal">&times;</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="pantry-tab" id="matches-tab">
                <div class="pantry-match-header">
                    <h2>Recipe Matches</h2>
                    <p>Discover recipes based on what you have in your pantry</p>
                </div>

                <form class="match-filters" method="POST" action="#matches-tab">
                    <div class="form-group">
                        <label for="match-threshold">
                            Match Threshold:
                            <select id="match-threshold" name="threshold">
                                <option value="0" <?= $threshold == 0 ? 'selected' : '' ?>>Show All Recipes</option>
                                <option value="50" <?= $threshold == 50 ? 'selected' : '' ?>>50% Match or Better</option>
                                <option value="75" <?= $threshold == 75 ? 'selected' : '' ?>>75% Match or Better</option>
                                <option value="90" <?= $threshold == 90 ? 'selected' : '' ?>>90% Match or Better</option>
                            </select>
                        </label>
                    </div>
                    <button type="submit" name="match-filters" class="button button-primary">Apply Filter</button>
                </form>
                <div class="recipe-matches" id="recipe-matches">
                    <div class="recipes-grid" id="recipe-results">
                        <?php while($row = $matching->fetch_assoc()): ?>
                                <div class="recipe-card">
                                    <div class="recipe-image">
                                        <img src="<?= htmlspecialchars($row['image_url']) ?: 'default.jpg' ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                                    </div>
                                    <div class="recipe-content">
                                        <h3 class="recipe-title">
                                            <a href="recipe_detail.php?id=<?= $row['RECIPE_ID'] ?>&source=<?= $row['source'] ?>">
                                                <?= htmlspecialchars($row['name']) ?>
                                            </a>
                                        </h3>
                                        <div class="recipe-meta">
                                            <div class="rating">
                                                <i class="fas fa-star"></i>
                                                <span><?= number_format($row['rating'], 1) ?>/5</span>
                                            </div>
                                            <div class="match-percentage">
                                            <?= $row['match_percentage'] ?>% Match</div>
                                        </div>
                                    </div>
                                </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'c:/xampp/htdocs/Recipes-main/partials/footer.php'; ?>

<script>
function toggleBookmark(el) {
    el.classList.toggle('active');
    const icon = el.querySelector('i');
    icon.classList.toggle('fas'); // filled
    icon.classList.toggle('far'); // outline
}

const navLinks = document.querySelectorAll(".pantry-nav-link");
const tabs = document.querySelectorAll(".pantry-tab");

navLinks.forEach(link => {
    link.addEventListener("click", function (e) {
        e.preventDefault();

        // Remove active class from all nav links and tabs
        navLinks.forEach(l => l.classList.remove("active"));
        tabs.forEach(t => t.classList.remove("active"));

        // Add active class to current link and corresponding tab
        this.classList.add("active");
        const targetTab = document.getElementById(this.dataset.tab);
        if (targetTab) {
            targetTab.classList.add("active");
        }
        window.location.hash = this.dataset.tab;
    });
});
window.addEventListener('load', function() {
    const hash = window.location.hash;
    if (hash) {
        const targetLink = document.querySelector(`.pantry-nav-link[href="${hash}"]`);
        if (targetLink) {
            targetLink.click();
        }
    }
});
const modal = document.getElementById('ingredient-modal');
const newIngBtn = document.querySelector('.new-ingredient');
const closeModalBtn = document.getElementById('close-ingredient');
const cancelModalBtn = document.getElementById('cancel-ingredient-btn');
const removeIngBtn = document.getElementById('close-modal'); 

newIngBtn.addEventListener('click', () => {
    modal.style.display = 'flex';
});

closeModalBtn.addEventListener('click', () => {
    modal.style.display = 'none';
});

cancelModalBtn.addEventListener('click', () => {
    modal.style.display = 'none';
});

removeIngBtn.addEventListener('click', () => {
    modal.classList.remove('active');
});

window.addEventListener('click', (e) => {
    if (e.target === modal) {
        modal.style.display = 'none';
    }
});
</script>
