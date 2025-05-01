<?php 
session_start(); 
session_destroy(); 
?>

<?php include '../partials/header.php'; ?>
<main class="main-content">
<div class="logout-container">
    <h2>You have been logged out!</h2>
    <p>Thank you for visiting. See you next time.</p>
    <a href="../auth/login.php" class="btn">Login Again</a>
</div>
</main>

<?php include '../partials/footer.php'; ?>

