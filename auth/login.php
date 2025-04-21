<?php include '../partials/header.php'; ?>
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <a href="/index.php" class="logo">CookCraft</a>
            <h1>Welcome Back</h1>
            <p>Login to access your recipes</p>
        </div>
        <form class="auth-form" action="login_process.php" method="POST">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="submit" class="button button-primary w-full">Log In</button>
            <p class="auth-redirect">
                Don't have an account? <a href="/ProjectDB/auth/register.php">Sign up</a>
            </p>
        </form>
    </div>
</div>
<?php include '../partials/footer.php'; ?>
