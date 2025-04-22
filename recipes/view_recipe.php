<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$conn = require __DIR__ . "/../config/db.php";

if (!isset($_GET['id']) || !isset($_GET['source'])) {
    die("Invalid recipe!");
}

$recipe_id = intval($_GET['id']);
$source = $_GET['source'];  // 'user' or 'system'

// Fetch recipe data depending on source
if ($source === 'user') {
    $stmt = $conn->prepare("SELECT name FROM user_submitted_recipes WHERE recipe_id = ?");
} else {
    $stmt = $conn->prepare("SELECT name FROM recipes WHERE recipe_id = ?");
}
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$result = $stmt->get_result();
$recipe = $result->fetch_assoc();
$stmt->close();

if (!$recipe) {
    die("Recipe not found.");
}

// Fetch ingredients
$ingredientSql = "
    SELECT i.name 
    FROM recipe_ingredients ri
    JOIN ingredients i ON ri.ingredient_id = i.ingredient_id
    WHERE ri.recipe_id = ?
";
$stmt = $conn->prepare($ingredientSql);
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$ingredientResult = $stmt->get_result();
$ingredients = [];
while ($row = $ingredientResult->fetch_assoc()) {
    $ingredients[] = $row['name'];
}
$stmt->close();

// Fetch instructions
$stmt = $conn->prepare("SELECT step_number, description FROM instructions WHERE recipe_id = ? ORDER BY step_number ASC");
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$instructionResult = $stmt->get_result();
$instructions = [];
while ($row = $instructionResult->fetch_assoc()) {
    $instructions[] = $row;
}
$stmt->close();
?>

<?php include '../partials/header.php'; ?>

<div class="container">
    <h1><?= htmlspecialchars($recipe['name']); ?></h1>

    <h3>Ingredients:</h3>
    <ul>
        <?php foreach ($ingredients as $ingredient): ?>
            <li><?= htmlspecialchars($ingredient); ?></li>
        <?php endforeach; ?>
    </ul>

    <h3>Instructions:</h3>
    <ol>
        <?php foreach ($instructions as $step): ?>
            <li><?= htmlspecialchars($step['description']); ?></li>
        <?php endforeach; ?>
    </ol>
</div>

<?php include '../partials/footer.php'; ?>