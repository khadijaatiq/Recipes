<?php 
session_start();
require_once '../config/db.php';
// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: " . 'auth/login.php');
    exit();
}

// Initialize variables
$error = '';
$success = '';
$recipe = null;
$ingredients = [];
$instructions = [];

// Validate recipe ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $error = "Invalid recipe ID";
} else {
    $recipe_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Fetch recipe
    $sql = "SELECT * FROM recipes WHERE RECIPE_ID = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $recipe_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recipe = $result->fetch_assoc();

    if (!$recipe) {
        $error = "Recipe not found or you don't have permission to edit it.";
    } else {
        // Fetch ingredients
        $stmt = $conn->prepare("SELECT i.ingredient_id, i.name, ri.quantity 
                               FROM recipe_ingredients ri 
                               JOIN ingredients i ON ri.ingredient_id = i.ingredient_id 
                               WHERE ri.recipe_id = ?");
        $stmt->bind_param("i", $recipe_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $ingredients[] = $row;

        // Fetch instructions
        $stmt = $conn->prepare("SELECT step_number, description 
                               FROM instructions 
                               WHERE recipe_id = ? 
                               ORDER BY step_number");
        $stmt->bind_param("i", $recipe_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $instructions[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_recipe'])) {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "CSRF token validation failed";
    } else {
        $recipe_id = intval($_POST['recipe_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $cooking_time = intval($_POST['cooking_time']);
        $image_url = trim($_POST['image_url']);
        $status = $_POST['status'];

        // Input validation
        if (empty($name)) {
            $error = "Recipe name is required";
        } elseif ($cooking_time <= 0) {
            $error = "Cooking time must be a positive number";
        } elseif (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
            $error = "Invalid image URL format";
        }

        if (empty($error)) {
            // Begin transaction
            $conn->begin_transaction();

            try {
                // Update main recipe info
                $stmt = $conn->prepare("UPDATE recipes 
                                      SET name = ?, description = ?, cooking_time = ?, 
                                          image_url = ?, status = ? 
                                      WHERE RECIPE_ID = ? AND user_id = ?");
                $stmt->bind_param("ssissii", $name, $description, $cooking_time, 
                                 $image_url, $status, $recipe_id, $user_id);
                $stmt->execute();

                if ($stmt->affected_rows === 0) {
                    throw new Exception("No changes made or recipe not found");
                }

                // Delete existing ingredients
                $stmt = $conn->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?");
                $stmt->bind_param("i", $recipe_id);
                $stmt->execute();

                // Insert new ingredients
                if (isset($_POST['ingredient_name'])) {
                    foreach ($_POST['ingredient_name'] as $i => $name) {
                        $name = trim($name);
                        $quantity = trim($_POST['ingredient_quantity'][$i] ?? '');

                        if (!empty($name)) {
                            // Get or create ingredient
                            $stmt = $conn->prepare("SELECT ingredient_id FROM ingredients WHERE name = ?");
                            $stmt->bind_param("s", $name);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                $ingredient_id = $result->fetch_assoc()['ingredient_id'];
                            } else {
                                $stmt = $conn->prepare("INSERT INTO ingredients (name) VALUES (?)");
                                $stmt->bind_param("s", $name);
                                $stmt->execute();
                                $ingredient_id = $stmt->insert_id;
                            }

                            // Link to recipe
                            $stmt = $conn->prepare("INSERT INTO recipe_ingredients 
                                                   (recipe_id, ingredient_id, quantity) 
                                                   VALUES (?, ?, ?)");
                            $stmt->bind_param("iis", $recipe_id, $ingredient_id, $quantity);
                            $stmt->execute();
                        }
                    }
                }

                // Update instructions
                $stmt = $conn->prepare("DELETE FROM instructions WHERE recipe_id = ?");
                $stmt->bind_param("i", $recipe_id);
                $stmt->execute();

                if (isset($_POST['steps'])) {
                    foreach ($_POST['steps'] as $i => $step) {
                        $step = trim($step);
                        if (!empty($step)) {
                            $step_number = $i + 1;
                            $stmt = $conn->prepare("INSERT INTO instructions 
                                                  (recipe_id, step_number, description) 
                                                  VALUES (?, ?, ?)");
                            $stmt->bind_param("iis", $recipe_id, $step_number, $step);
                            $stmt->execute();
                        }
                    }
                }

                // Commit transaction
                $conn->commit();
                $_SESSION['flash_message'] = "Recipe updated successfully!";
                header("Location: bookmarks.php");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Failed to update recipe: " . $e->getMessage();
            }
        }
    }
}

include 'C:/xampp/htdocs/Recipes-main/partials/header.php';
?>

<main class="main-content">
    <section class="section">
        <div class="container">
            <h1>Edit Recipe</h1>
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['flash_message']) ?>
                    <?php unset($_SESSION['flash_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($recipe): ?>
            <form method="post" class="recipe-form">
                <input type="hidden" name="edit_recipe" value="1">
                <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($recipe['NAME']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="4"><?= htmlspecialchars($recipe['DESCRIPTION']) ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cooking_time">Cooking Time (minutes):</label>
                        <input type="number" id="cooking_time" name="cooking_time" 
                               value="<?= htmlspecialchars($recipe['COOKING_TIME']) ?>" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status">
                            <option value="public" <?= $recipe['status']=='public'?'selected':'' ?>>Public</option>
                            <option value="private" <?= $recipe['status']=='private'?'selected':'' ?>>Private</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image_url">Image URL:</label>
                    <input type="url" id="image_url" name="image_url" 
                           value="<?= htmlspecialchars($recipe['IMAGE_URL']) ?>" 
                           placeholder="https://example.com/image.jpg">
                </div>
                
                <div class="form-group">
                    <h3>Ingredients</h3>
                    <div id="ingredients-list">
                        <?php foreach ($ingredients as $i => $ing): ?>
                            <div class="ingredient-row">
                                <input type="text" name="ingredient_name[]" 
                                       value="<?= htmlspecialchars($ing['name']) ?>" 
                                       placeholder="Ingredient name" required>
                                <input type="text" name="ingredient_quantity[]" 
                                       value="<?= htmlspecialchars($ing['quantity']) ?>" 
                                       placeholder="Quantity">
                                <button type="button" class="button button-outline remove-btn" onclick="this.parentNode.remove()">✖</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button button-outline btn-add" onclick="addIngredient()">+ Add Ingredient</button>
                </div>
                
                <div class="form-group">
                    <h3>Instructions</h3>
                    <div id="instructions-list">
                        <?php foreach ($instructions as $i => $step): ?>
                            <div class="instruction-row">
                                <textarea name="steps[]" placeholder="Step <?= $i + 1 ?>"><?= htmlspecialchars($step['description']) ?></textarea>
                                <button type="button" class="button button-outline remove-btn" onclick="this.parentNode.remove()">✖</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button button-outline btn-add" onclick="addInstruction()">+ Add Instruction</button>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary">Save Changes</button>
                    <a href="<?= "recipe_details.php?id=$recipe_id" ?>" class="btn-cancel">Cancel</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
function addIngredient() {
    const container = document.getElementById('ingredients-list');
    const div = document.createElement('div');
    div.className = 'ingredient-row';
    div.innerHTML = `
        <input type="text" name="ingredient_name[]" placeholder="Ingredient name" required>
        <input type="text" name="ingredient_quantity[]" placeholder="Quantity">
        <button type="button" class="remove-btn" onclick="this.parentNode.remove()">✖</button>
    `;
    container.appendChild(div);
    container.lastElementChild.querySelector('input').focus();
}

function addInstruction() {
    const container = document.getElementById('instructions-list');
    const div = document.createElement('div');
    div.className = 'instruction-row';
    div.innerHTML = `
        <textarea name="steps[]" placeholder="Step ${container.children.length + 1}"></textarea>
        <button type="button" class="remove-btn" onclick="this.parentNode.remove()">✖</button>
    `;
    container.appendChild(div);
    container.lastElementChild.querySelector('textarea').focus();
}

// Prevent form resubmission
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>
<?php include '../partials/footer.php'; ?>
