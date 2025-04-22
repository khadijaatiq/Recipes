<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$conn = require __DIR__ . "/../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve form inputs
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $cuisine = trim($_POST['cuisine']);
    $cooking_time = intval($_POST['cooking_time']);
    $image_url = trim($_POST['image_url']);
    $user_id = $_SESSION['user_id'];
    $status = trim($_POST['status']);

    // Insert the new recipe into user_submitted_recipes
    $sql = "INSERT INTO user_submitted_recipes (user_id, name, description, cuisine, cooking_time, image_url, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssiss", $user_id, $name, $description, $cuisine, $cooking_time, $image_url, $status);

    if ($stmt->execute()) {
        $recipe_id = $stmt->insert_id;
        $stmt->close();

        // Insert ingredients
        $names = $_POST['ingredient_name'];
        $quantities = $_POST['ingredient_quantity'];
        for ($i = 0; $i < count($names); $i++) {
            $ingredient_name = trim($names[$i]);
            $quantity = trim($quantities[$i]);
            if ($ingredient_name === '') continue;

            // Check if ingredient exists
            $stmtCheck = $conn->prepare("SELECT ingredient_id FROM ingredients WHERE name = ?");
            $stmtCheck->bind_param("s", $ingredient_name);
            $stmtCheck->execute();
            $result = $stmtCheck->get_result();
            if ($row = $result->fetch_assoc()) {
                $ingredient_id = $row['ingredient_id'];
            } else {
                // Insert new ingredient
                $stmtInsert = $conn->prepare("INSERT INTO ingredients (name) VALUES (?)");
                $stmtInsert->bind_param("s", $ingredient_name);
                $stmtInsert->execute();
                $ingredient_id = $stmtInsert->insert_id;
                $stmtInsert->close();
            }
            $stmtCheck->close();

            // Link ingredient to recipe
            $stmtLink = $conn->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity) VALUES (?, ?, ?)");
            $stmtLink->bind_param("iis", $recipe_id, $ingredient_id, $quantity);
            $stmtLink->execute();
            $stmtLink->close();
        }

        // Insert instructions
        if (!empty($_POST['steps'])) {
            foreach ($_POST['steps'] as $step_number => $instruction) {
                $instruction = trim($instruction);
                if ($instruction === '') continue;

                $step_sql = "INSERT INTO Instructions (recipe_id, step_number, description) VALUES (?, ?, ?)";
                $step_stmt = $conn->prepare($step_sql);
                $step_stmt->bind_param("iis", $recipe_id, $step_number, $instruction);
                $step_stmt->execute();
                $step_stmt->close();
            }
        }

        // Insert meal types
        if (!empty($_POST['meal_types'])) {
            foreach ($_POST['meal_types'] as $meal_name) {
                $stmtMeal = $conn->prepare("SELECT meal_id FROM meal_types WHERE name = ?");
                $stmtMeal->bind_param("s", $meal_name);
                $stmtMeal->execute();
                $result = $stmtMeal->get_result();
                if ($row = $result->fetch_assoc()) {
                    $meal_type_id = $row['meal_id'];
                    $stmtLink = $conn->prepare("INSERT INTO recipe_meal_types (recipe_id, meal_type_id) VALUES (?, ?)");
                    $stmtLink->bind_param("ii", $recipe_id, $meal_type_id);
                    $stmtLink->execute();
                    $stmtLink->close();
                }
                $stmtMeal->close();
            }
        }

        // Insert dietary restrictions
        if (!empty($_POST['dietary_restrictions'])) {
            foreach ($_POST['dietary_restrictions'] as $restriction_name) {
                $stmtDiet = $conn->prepare("SELECT dietRes_id FROM dietary_restrictions WHERE name = ?");
                $stmtDiet->bind_param("s", $restriction_name);
                $stmtDiet->execute();
                $result = $stmtDiet->get_result();
                if ($row = $result->fetch_assoc()) {
                    $restriction_id = $row['dietRes_id'];
                    $stmtLink = $conn->prepare("INSERT INTO recipe_dietary_restrictions (recipe_id, restriction_id) VALUES (?, ?)");
                    $stmtLink->bind_param("ii", $recipe_id, $restriction_id);
                    $stmtLink->execute();
                    $stmtLink->close();
                }
                $stmtDiet->close();
            }
        }

        // Automatically bookmark the recipe for the user
        $bookmarkSql = "INSERT IGNORE INTO bookmarks (user_id, recipe_id, recipe_source) VALUES (?, ?, 'user')";
        $bookmarkStmt = $conn->prepare($bookmarkSql);
        $bookmarkStmt->bind_param("ii", $user_id, $recipe_id);
        $bookmarkStmt->execute();
        $bookmarkStmt->close();

        // Redirect to bookmarks page with success message
        header("Location: ../recipes/bookmarks.php?success=recipe_bookmarked");
        exit();
    } else {
        $stmt->close();
        header("Location: add_recipe.php?error=recipe_insert_failed");
        exit();
    }
} else {
    header("Location: add_recipe.php");
    exit();
}
?>