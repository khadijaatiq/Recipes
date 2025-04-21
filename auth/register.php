<?php include '../partials/header.php'; ?>
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <a href="/ProjectDB/index.php" class="logo">CookCraft</a>
            <h1>Create Account</h1>
            <p>Join our cooking community today</p>
        </div>

        <!-- Display error or success messages -->
        <div id="error-message" class="error-message">
            <?php
            if (isset($_GET['error'])) {
                $error = $_GET['error'];
                if ($error == "password_mismatch") {
                    echo "❌ Passwords do not match!";
                } elseif ($error == "username_taken") {
                    echo "❌ Username or email already exists!";
                } else {
                    echo "❌ An unknown error occurred.";
                }
            } elseif (isset($_GET['success']) && $_GET['success'] == 'registered') {
                echo "✔️ Registration successful! Please log in.";
            }
            ?>
        </div>

        <form class="auth-form" action="register_process.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="button button-primary w-full">Sign Up</button>
            <p class="auth-redirect">
                Already have an account? <a href="/ProjectDB/auth/login.php">Log in</a>
            </p>
        </form>
    </div>
</div>

<script>
    const errorDiv = document.getElementById('error-message');
    if (errorDiv.innerHTML.trim() !== '') {
        errorDiv.style.display = "block";
    } else {
        errorDiv.style.display = "none";
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
