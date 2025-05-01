<?php
session_start();
require "c:/xampp/htdocs/Recipes-main/config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}


$user_id = $_SESSION['user_id'];
// Fetch user info
$stmt = $conn->prepare("SELECT username, email, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $created_at);
$stmt->fetch();
$stmt->close();

// Handle form submissions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Change username
    if (isset($_POST['change_username'])) {
        $new_username = trim($_POST['username']);
        if ($new_username && $new_username !== $username) {
            $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $check->bind_param("s", $new_username);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $error = "Username already taken.";
            } else {
                $update = $conn->prepare("UPDATE users SET username = ? WHERE user_id = ?");
                $update->bind_param("si", $new_username, $user_id);
                if ($update->execute()) {
                    $success = "Username updated!";
                    $username = $new_username;
                } else {
                    $error = "Failed to update username.";
                }
                $update->close();
            }
            $check->close();
        }
    }
    // Change password
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        // Fetch current hash
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($hash);
        $stmt->fetch();
        $stmt->close();
        if (!password_verify($current, $hash)) {
            $error = "Current password is incorrect.";
        } elseif ($new !== $confirm) {
            $error = "New passwords do not match.";
        } elseif (strlen($new) < 6) {
            $error = "New password must be at least 6 characters.";
        } else {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $update->bind_param("si", $new_hash, $user_id);
            if ($update->execute()) {
                $success = "Password updated!";
            } else {
                $error = "Failed to update password.";
            }
            $update->close();
        }
    }
    // Delete account
    if (isset($_POST['delete_account'])) {
        // Optionally: delete user's recipes, bookmarks, etc.
        $del = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $del->bind_param("i", $user_id);
        if ($del->execute()) {
            session_destroy();
            header("Location: index.php?msg=account_deleted");
            exit();
        } else {
            $error = "Failed to delete account.";
        }
        $del->close();
    }
}

?>
<?php
 include 'c:\xampp\htdocs\Recipes-main/partials/header.php'; ?>
<main class="main-content">
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h1 class="section-title">My Profile</h1>
                <p class="section-subtitle">Manage your account and recipes</p>
            </div>
            <div class="profile-content">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php $profilePath = '/Recipes-main/images/profile.png'?>
                            <img src=<?= $profilePath ?> alt="Profile Avatar">
                        </div>
                        <div class="profile-info">
                            <h2><?= htmlspecialchars($username) ?></h2>
                            <p>Member since <?= date('F Y', strtotime($created_at)) ?></p>
                            <!-- You can add stats here if you want -->
                        </div>
                    </div>
                    <div class="account-settings">
                        <h3>Account Settings</h3>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        <form class="settings-form" method="post">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" class="form-input">
                                <button type="submit" name="change_username" class="button button-outline">Change Username</button>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" value="<?= htmlspecialchars($email) ?>" class="form-input" readonly>
                            </div>
                        </form>
                        <form class="settings-form" method="post">
                            <div class="form-group">
                                <label for="current-password">Current Password</label>
                                <input type="password" id="current-password" name="current_password" class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="new-password">New Password</label>
                                <input type="password" id="new-password" name="new_password" class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="confirm-password">Confirm New Password</label>
                                <input type="password" id="confirm-password" name="confirm_password" class="form-input">
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="change_password" class="button button-primary">Save Changes</button>
                            </div>
                        </form>
                        <div class="danger-zone">
                            <h3>Danger Zone</h3>
                            <p>Once you delete your account, there is no going back. Please be certain.</p>
                            <form method="post" onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone.');">
                                <button type="submit" name="delete_account" class="button button-danger">Delete Account</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include 'c:\xampp\htdocs\Recipes-main/partials/footer.php'; ?>
