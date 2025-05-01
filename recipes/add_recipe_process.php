<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$conn = require __DIR__ . "/../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $cooking_time = intval($_POST['cooking_time']);
    $image_url = trim($_POST['image_url']);
    $status = trim($_POST['status']);

    $conn->begin_transaction();
    try {
        // --- Call the stored procedure to add recipe ---
        $stmt = $conn->prepare("CALL AddUserRecipe(?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $user_id, $name, $description, $cooking_time, $image_url);
        
        if ($stmt->execute()) {
            // Get the new recipe ID from the procedure result
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $recipe_id = $row['new_recipe_id'];
            
            // Free the result set
            $stmt->free_result();
            $stmt->close();
            
            // --- Update the status (since procedure sets it to 'private') ---
            if ($status !== 'private') {
                $updateStmt = $conn->prepare("UPDATE recipes SET status = ? WHERE recipe_id = ?");
                $updateStmt->bind_param("si", $status, $recipe_id);
                $updateStmt->execute();
                $updateStmt->close();
            }

            // --- Insert Ingredients ---
            if (!empty($_POST['ingredient_name']) && !empty($_POST['ingredient_quantity'])) {
                $names = $_POST['ingredient_name'];
                $quantities = $_POST['ingredient_quantity'];

                for ($i = 0; $i < count($names); $i++) {
                    $iname = trim($names[$i]);
                    $qty = trim($quantities[$i]);
                    if ($iname === '') continue;

                    $stmtCheck = $conn->prepare("SELECT ingredient_id FROM ingredients WHERE name = ?");
                    $stmtCheck->bind_param("s", $iname);
                    $stmtCheck->execute();
                    $result = $stmtCheck->get_result();

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $ingredient_id = $row['ingredient_id'];
                    } else {
                        $stmtInsert = $conn->prepare("INSERT INTO ingredients (name) VALUES (?)");
                        $stmtInsert->bind_param("s", $iname);
                        $stmtInsert->execute();
                        $ingredient_id = $stmtInsert->insert_id;
                        $stmtInsert->close();
                    }
                    $stmtCheck->close();

                    $stmtLink = $conn->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity) VALUES (?, ?, ?)");
                    $stmtLink->bind_param("iis", $recipe_id, $ingredient_id, $qty);
                    $stmtLink->execute();
                    $stmtLink->close();
                }
            }

            // --- Insert Instructions ---
            if (!empty($_POST['steps'])) {
                foreach ($_POST['steps'] as $step_number => $instruction) {
                    $stmtStep = $conn->prepare("INSERT INTO instructions (recipe_id, step_number, description) 
                                              VALUES (?, ?, ?)");
                    $stmtStep->bind_param("iis", $recipe_id, $step_number, $instruction);
                    $stmtStep->execute();
                    $stmtStep->close();
                }
            }

            // --- Link Recipe and Cuisine ---
            if (!empty($_POST['cuisine'])) {
                $cuisine = $_POST['cuisine'];
                
                $stmtCuisine = $conn->prepare("SELECT cuisine_id FROM cuisines WHERE name = ?");
                $stmtCuisine->bind_param("s", $cuisine);
                $stmtCuisine->execute();
                $cuisineRes = $stmtCuisine->get_result()->fetch_assoc();
                
                if ($cuisineRes) {
                    $cuisine_id = $cuisineRes['cuisine_id'];
                    
                    $linkCuisine = $conn->prepare("INSERT INTO recipe_cuisines (recipe_id, cuisine_id) VALUES (?, ?)");
                    $linkCuisine->bind_param("ii", $recipe_id, $cuisine_id);
                    $linkCuisine->execute();
                    $linkCuisine->close();
                }
                $stmtCuisine->close();
            }

            // --- Insert Meal Types ---
            if (!empty($_POST['meal_types'])) {
                foreach ($_POST['meal_types'] as $meal) {
                    $stmtMeal = $conn->prepare("SELECT id FROM meal_types WHERE name = ?");
                    $stmtMeal->bind_param("s", $meal);
                    $stmtMeal->execute();
                    $mealRes = $stmtMeal->get_result()->fetch_assoc();

                    if ($mealRes) {
                        $meal_type_id = $mealRes['id'];
                        $linkMeal = $conn->prepare("INSERT INTO recipe_meal_types (recipe_id, meal_type_id) VALUES (?, ?)");
                        $linkMeal->bind_param("ii", $recipe_id, $meal_type_id);
                        $linkMeal->execute();
                        $linkMeal->close();
                    }
                    $stmtMeal->close();
                }
            }

            // --- Insert Dietary Restrictions ---
            if (!empty($_POST['dietary_restrictions'])) {
                foreach ($_POST['dietary_restrictions'] as $diet) {
                    $stmtDiet = $conn->prepare("SELECT id FROM dietary_restrictions WHERE name = ?");
                    $stmtDiet->bind_param("s", $diet);
                    $stmtDiet->execute();
                    $dietRes = $stmtDiet->get_result()->fetch_assoc();

                    if ($dietRes) {
                        $restriction_id = $dietRes['id'];
                        $linkDiet = $conn->prepare("INSERT INTO recipe_dietary_restrictions (recipe_id, restriction_id) VALUES (?, ?)");
                        $linkDiet->bind_param("ii", $recipe_id, $restriction_id);
                        $linkDiet->execute();
                        $linkDiet->close();
                    }
                    $stmtDiet->close();
                }
            }

            // --- Bookmark it for user ---
            $bookmark = $conn->prepare("INSERT INTO bookmarks (user_id, recipe_id) VALUES (?, ?)");
            $bookmark->bind_param("ii", $user_id, $recipe_id);
            $bookmark->execute();
            $bookmark->close();

            $conn->commit();
            header("Location: ../recipes/bookmarks.php?success=recipe_added");
            exit();
        } else {
            throw new Exception("Failed to call AddUserRecipe procedure.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage() . "<br>MySQL error: " . $conn->error;
        exit();
    }
} else {
    header("Location: add_recipe.php");
    exit();
}
?>
