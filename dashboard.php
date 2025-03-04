<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
?>

<?php include 'partials/header.php'; ?>
<?php include 'partials/navbar.php'; ?>

<h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
<a href="auth/logout.php">Logout</a>

<?php include 'partials/footer.php'; ?>