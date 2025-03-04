<?php include '../partials/header.php'; ?>
<?php include '../partials/navbar.php'; ?>
<div class="container">
<div class ="box">
    <h2>Register</h2>
    <div id="error-message" class="error-message">this is a test error</div>
<form action="register_process.php" method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="submit">Register</button>
</form>
</div>
</div>

<script>
    // Get error from URL and display as styled message
    const urlParams = new URLSearchParams(window.location.search);
    const errorMessage = urlParams.get('error');

    if (errorMessage) {
        const errorDiv = document.getElementById('error-message');
        if (errorMessage === "password_mismatch") {
            errorDiv.innerHTML = "❌ Passwords do not match!";
        } else if (errorMessage === "username_taken") {
            errorDiv.innerHTML = "❌ Username or email already exists!";
        } else {
            errorDiv.innerHTML = "❌ An unknown error occurred.";
        }
        errorDiv.style.display = "block";
    }
</script>

<style>
    .error-message {
        display: none;
        background-color: #ffdddd;
        color: red;
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid red;
        border-radius: 5px;
    }
</style>
<?php include '../partials/footer.php'; ?>