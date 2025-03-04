<?php include '../partials/header.php'; ?>
<?php include '../partials/navbar.php'; ?>
<div class=container>
    <div class="box">
        <h2>Login</h2>
        <form action="login_process.php" method="POST">
            <input type="text" name="username" placeholder="Username or Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="submit">Login</butto>
        </form>
    </div>

</div>
<h1>Login</h1>


<?php include '../partials/footer.php'; ?>
