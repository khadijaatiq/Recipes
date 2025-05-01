<?php include '../partials/header.php'; ?>

<?php
if (!file_exists('c:/xampp/htdocs/Recipes-Main/config/db.php')) {
    die("Database file not found!");
}

require_once 'c:/xampp/htdocs/Recipes-Main/config/db.php';
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
<style> .checkbox-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    
    .checkbox-grid label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-weight: normal;
    }</style>
<body>
<div class="main-content">
    <div class="recipe-form-container" id="recipe-form-modal">
        <div class="recipe-form-content">
            <div class="recipe-form-header">
                <h2>Create New Recipe</h2>
            </div>
            
            <form id="recipe-form" method="POST" action="add_recipe_process.php">
                <!-- Basic Information Section -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="recipe-name">Recipe name*</label>
                        <input type="text" name="name" id="recipe-name" required>
                    </div>
                    <div class="form-group">
                        <label for="recipe-desc">Recipe Description</label>
                        <input type="text" name="description" id="recipe-desc">
                    </div>
                </div>
                
                <!-- Status and Cooking Time -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <div class="visibility-options">
                            <label><input type="radio" name="status" value="private" checked> Private</label>
                            <label><input type="radio" name="status" value="public" id="public-option"> Public</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="recipe-cook-time">Cook Time (min)*</label>
                        <input type="number" name="cooking_time" id="recipe-cook-time" min="0" required>
                    </div>
                </div>
                
                <!-- Meal Types and Dietary Restrictions -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Meal Types</label>
                        <div class="checkbox-grid">
                            <label><input type="checkbox" name="meal_types[]" value="Snack"> Snack</label>
                            <label><input type="checkbox" name="meal_types[]" value="Dessert"> Dessert</label>
                            <label><input type="checkbox" name="meal_types[]" value="Brunch"> Brunch</label>
                            <label><input type="checkbox" name="meal_types[]" value="Breakfast"> Breakfast</label>
                            <label><input type="checkbox" name="meal_types[]" value="Lunch"> Lunch</label>
                            <label><input type="checkbox" name="meal_types[]" value="Dinner"> Dinner</label>
                            <label><input type="checkbox" name="meal_types[]" value="Side Dish"> Side Dish</label>
                            <label><input type="checkbox" name="meal_types[]" value="Sauce"> Sauce</label>
                            <label><input type="checkbox" name="meal_types[]" value="Beverage"> Beverage</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Dietary Restrictions</label>
                        <div class="checkbox-grid">
                            <label><input type="checkbox" name="dietary_restrictions[]" value="Vegetarian"> Vegetarian</label>
                            <label><input type="checkbox" name="dietary_restrictions[]" value="Vegan"> Vegan</label>
                            <label><input type="checkbox" name="dietary_restrictions[]" value="Gluten-Free"> Gluten-Free</label>
                            <label><input type="checkbox" name="dietary_restrictions[]" value="Dairy-Free"> Dairy-Free</label>
                            <label><input type="checkbox" name="dietary_restrictions[]" value="Keto"> Keto</label>
                            <label><input type="checkbox" name="dietary_restrictions[]" value="Low-Carb"> Low-Carb</label>
                        </div>
                    </div>
                </div>
                
                <!-- Cuisine and Image -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="recipe-cuisine">Cuisine Type*</label>
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
                    <div class="form-group">
                        <label for="recipe-image">Recipe Image URL</label>
                        <input type="url" name="image_url" id="recipe-image" placeholder="https://example.com/image.jpg">
                    </div>
                </div>
                
                <!-- Ingredients Section -->
                <div class="form-group">
                    <label>Ingredients*</label>
                    <div id="ingredients">
                        <div class="ingredient-row">
                            <input type="text" name="ingredient_name[]" placeholder="Name" list="ingredient-list" required>
                            <input type="text" name="ingredient_quantity[]" placeholder="Quantity">
                            <button type="button" class="button button-outline remove-ingredient">✕</button>
                        </div>
                    </div>
                    <button type="button" class="button button-outline add-ingredient">+ Add Ingredient</button>
                    <datalist id="ingredient-list">
                        <?php foreach ($ingredientOptions as $option): ?>
                            <option value="<?= htmlspecialchars($option) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <!-- Instructions Section -->
                <div class="form-group">
                    <label>Instructions*</label>
                    <div id="steps">
                        <div class="instruction-step">
                            <input type="text" name="steps[1]" placeholder="Step 1 description" required>
                            <button type="button" class="button button-outline remove-instruction">✕</button>
                        </div>
                    </div>
                    <button type="button" class="button button-outline add-step" onclick="addStep()">+ Add Step</button>
                </div>
                
                <!-- Submit Button -->
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
    
    document.addEventListener('DOMContentLoaded', () => {
        // Ingredients functionality
        const ingredientsDiv = document.getElementById('ingredients');
        const stepsDiv = document.getElementById('steps');

        const addIngredientRow = () => {
            const row = document.createElement('div');
            row.className = 'ingredient-row';
            row.innerHTML = `
                <input type="text" name="ingredient_name[]" placeholder="name" list="ingredient-list" required>
                <input type="text" name="ingredient_quantity[]" placeholder="Qty">
                <button type="button" class="button button-outline remove-ingredient">✕</button>
            `;
            ingredientsDiv.appendChild(row);
        };

        const addStep = () => {
            stepCount++;
            const row = document.createElement('div');
            row.className = 'instruction-row';
            row.innerHTML = `
                <input type="text" name="steps[${stepCount}]" placeholder="Step ${stepCount} description" required>
                <button type="button" class="button button-outline remove-instruction">✕</button>
            `;
            stepsDiv.appendChild(row);
        };

        // Add ingredient button
        document.querySelector('.add-ingredient').onclick = addIngredientRow;
        
        // Add step button
        document.querySelector('.add-step').onclick = addStep;

        // Remove ingredient/instruction handlers
        document.getElementById('ingredients').addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-ingredient')) {
                e.target.parentElement.remove();
            }
        });

        document.getElementById('steps').addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-instruction')) {
                e.target.parentElement.remove();
                // You might want to renumber the steps here if needed
            }
        });
    });
</script> 

<?php include '../partials/footer.php'; ?>
