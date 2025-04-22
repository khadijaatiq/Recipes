<?php include '../partials/header.php'; ?>

<?php
if (!file_exists('c:/xampp/htdocs/ProjectDB/config/db.php')) {
    die("Database file not found!");
}

require_once 'c:/xampp/htdocs/ProjectDB/config/db.php';

// Fetch all ingredients from database (run this at the top of your page)
$ingredientOptions = [];
$stmt = $conn->prepare("SELECT name FROM ingredients ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $ingredientOptions[] = $row['name'];
}
$stmt->close();
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'recipe_added') {
        echo "<p style='color: green;'>Recipe successfully added!</p>";
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'recipe_insert_failed') {
        echo "<p style='color: red;'>Recipe could not be added. Please try again later.</p>";
    }
}
?>
<body>
<div class="main-content">
 <div class="recipe-form-container" id="recipe-form-modal">
    <div class="recipe-form-content">
        <div class="recipe-form-header">
            <h2>Create New Recipe</h2>
        </div>
        <form id="recipe-form" method="POST" action="add_recipe_process.php">
            <div class="form-group">
                <label for="recipe-name">Recipe name*</label>
                <input type="text" name="name" id="recipe-name" required>
            </div>
            <div class="form-group">
                <label for="recipe-desc">Recipe Description</label>
                <input type="text" name="description" id="recipe-desc">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <div class="visibility-options">
                <label><input type="radio" name="status" value="private" checked> Private</label>
                <label><input type="radio" name="status" value="public" id="public-option"> Public</label>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="meal-types">Meal Types</label><br>
                    <div class="checkbox-grid">
                    <label><input type="checkbox" name="meal_types[]" value="Snack"> Snack</label><br>
                    <label><input type="checkbox" name="meal_types[]" value="Dessert"> Dessert</label><br>
                    <label><input type="checkbox" name="meal_types[]" value="Brunch"> Brunch</label><br>
                    <label><input type="checkbox" name="meal_types[]" value="Breakfast"> Breakfast</label><br>
                    <label><input type="checkbox" name="meal_types[]" value="Lunch"> Lunch</label><br>
                    <label><input type="checkbox" name="meal_types[]" value="Dinner"> Dinner</label><br>
                </div>
            </div>

            <div class="form-group">
                        <label for="dietary-restrictions">Dietary Restrictions</label><br>
                <div class="checkbox-grid">
                    <label class="dietary-checkbox"><input type="checkbox" name="dietary_restrictions[]" value="Vegetarian"> Vegetarian</label><br>
                    <label class="dietary-checkbox"><input type="checkbox" name="dietary_restrictions[]" value="Vegan"> Vegan</label><br>
                    <label class="dietary-checkbox"><input type="checkbox" name="dietary_restrictions[]" value="Gluten-Free"> Gluten-Free</label><br>
                    <label class="dietary-checkbox"><input type="checkbox" name="dietary_restrictions[]" value="Dairy-Free"> Dairy-Free</label><br>
                    <label class="dietary-checkbox"><input type="checkbox" name="dietary_restrictions[]" value="Keto"> Keto</label><br>
                    <label class="dietary-checkbox"><input type="checkbox" name="dietary_restrictions[]" value="Low-Carb"> Low-Carb</label><br>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="recipe-cook-time">Cook Time (min)*</label>
                    <input type="number" name="cooking_time" id="recipe-cook-time" min="0" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="recipe-cuisine" >Cuisine Type*</label>
                    <select id="recipe-cuisine" name="cuisine" required>
                        <option value="">Select cuisine</option>
                        <option value="Italian">Italian</option>
                        <option value="Chinese">Chinese</option>
                        <option value="Indian">Indian</option>
                        <option value="Mexican">Mexican</option>
                        <option value="Thai">Thai</option>
                        <option value="Japanese">Japanese</option>
                        <option value="American">American</option>
                        <option value="French">French</option>
                        <option value="Mediterranean">Mediterranean</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="recipe-image">Recipe Image URL</label>
                <input type="url" name="image_url" id="recipe-image" placeholder="https://example.com/image.jpg">
            </div>
            <div class="form-group" id="ingredients">
                <label for="recipe-ingredients">Ingredients*</label>
                <div class="ingredient-row">
                    <input type="text" name="ingredient_name[]" placeholder="name" list="ingredient-list" required>
                    <input type="text" name="ingredient_quantity[]" placeholder="Qty">
                    <button type="button" class="button button-outline remove-ingredient">✕</button>
                </div>
            </div>
            <button type="button" class="button button-outline add-ingredient">+ Add Ingredient</button>
            <datalist id="ingredient-list">
                <?php foreach ($ingredientOptions as $option): ?>
                    <option value="<?= htmlspecialchars($option) ?>">
                <?php endforeach; ?>
            </datalist>


            <div class="form-group">
                <label for="recipe-instructions">Instructions*</label>
                <div id="steps">
                <input type="text" name="steps[1]" placeholder="Step 1 description" required>
                </div>
                <button type="button" class="button button-outline add-step" onclick="addStep()">+ Add Step</button>
            </div>
            <div class="form-actions">
                <button type="submit" name="submit" class="button button-primary">Create Recipe</button>
            </div>
        </form>
    </div>
</div> 
</div>
</body>

<script>
    let stepCount = 1;
function addStep() {
    stepCount++;
    let container = document.getElementById("steps");
    let input = document.createElement("input");
    input.type = "text";
    input.name = "steps[" + stepCount + "]";
    input.placeholder = "Step " + stepCount + " description";
    container.appendChild(input);
}
document.addEventListener('DOMContentLoaded', () => {
    const ingredientsDiv = document.getElementById('ingredients');

    const addIngredientRow = () => {
        const row = document.createElement('div');
        row.classname = 'ingredient-row';
        row.innerHTML = `
            <input type="text" name="ingredient_name[]" placeholder="name" list="ingredient-list" required>
            <input type="text" name="ingredient_quantity[]" placeholder="Qty">
            <button type="button" class="button button-outline remove-ingredient" ">✕</button>
        `;
        ingredientsDiv.appendChild(row);
    };

    const addBtn = document.querySelector('.add-ingredient');
    addBtn.onclick = addIngredientRow;
    ingredientsDiv.parentNode.insertBefore(addBtn, ingredientsDiv.nextSibling);

    ingredientsDiv.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-ingredient')) {
            e.target.parentElement.remove();
        }
    });
});
</script> 

<?php include '../partials/footer.php'; ?>